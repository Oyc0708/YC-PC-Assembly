<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Pool;
use App\Models\ComponentPrice;

class ScrapeComponentPrice implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $vendors = [
        'Ideal Tech PC' => [
            'search_url' => 'https://idealtech.com.my/?post_type=product&s=',
            'card_xpath' => "//div[contains(@class, 'product') or contains(@class, 'type-product')]",
            'price_xpath'=> ".//span[contains(@class, 'woocommerce-Price-amount')]",
            'title_xpath'=> ".//h2[contains(@class, 'woocommerce-loop-product__title')]",
        ],
        'Brightstar Computer' => [
            'search_url' => 'https://brightstarcomp.com/search?q=',
            'card_xpath' => "//*[contains(@class, 'product-item')]",
            'price_xpath'=> ".//*[contains(@class, 'price')]",
            'title_xpath'=> ".//*[contains(@class, 'product-item-link') or contains(@class, 'product-title')]",
        ],
    ];

    public function __construct(
        public mixed $component,
        public string $componentClass
    ) {}

    public function handle(): void
    {
        error_reporting(E_ALL & ~E_DEPRECATED);

        $cleanedName = $this->cleanSearchTerm($this->component->name);
        $searchQuery = urlencode($cleanedName);

        $responses = Http::pool(function (Pool $pool) use ($searchQuery) {
            $requests = [];
            foreach ($this->vendors as $vendorName => $config) {
                $targetUrl = $config['search_url'] . $searchQuery;
                $requests[$vendorName] = $pool->as($vendorName)
                    ->withoutVerifying()
                    ->withHeaders([
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36'
                    ])
                    ->timeout(8)
                    ->get($targetUrl);
            }
            return $requests;
        });

        $lowestPriceFound = PHP_INT_MAX;

        foreach ($responses as $vendorName => $response) {
            if ($response instanceof \Throwable || !$response->successful()) {
                continue;
            }

            $config = $this->vendors[$vendorName];
            $targetUrl = $config['search_url'] . $searchQuery;
            $marketPrice = $this->extractPriceNative($response->body(), $config, $cleanedName);

            if ($marketPrice > 0) {
                ComponentPrice::updateOrCreate(
                    [
                        'component_type' => $this->componentClass,
                        'component_id'   => $this->component->id,
                        'vendor'         => $vendorName,
                    ],
                    [
                        'price'      => $marketPrice,
                        'url'        => $targetUrl,
                        'in_stock'   => true,
                        'updated_at' => now(),
                    ]
                );

                if ($marketPrice < $lowestPriceFound) {
                    $lowestPriceFound = $marketPrice;
                }
            }
        }

        if ($lowestPriceFound < PHP_INT_MAX) {
            $this->component->price = $lowestPriceFound;
            $this->component->save();
        } else {
            $this->component->touch();
        }
    }

    private function cleanSearchTerm(string $name): string
    {
        return trim(str_ireplace([' processor', ' box', ' edition', ' lhr'], '', $name));
    }

    /**
     * Highly strict word-by-word comparison. 
     * Rejects the product entirely if significant words from the expected name are missing.
     */
    private function isStrictMatch(string $expected, string $scraped): bool
    {
        // 1. Remove all punctuation and convert to lowercase
        $expectedClean = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $expected));
        $scrapedClean = strtolower(preg_replace('/[^a-z0-9]+/i', ' ', $scraped));

        // 2. Break the expected name into unique words
        $expectedWords = array_unique(array_filter(explode(' ', $expectedClean)));
        
        // 3. Define words that stores often format weirdly or omit entirely
        $ignoreList = [
            'ghz', 'mhz', 'gb', 'tb', 'w', 'hz', 'x', 'core', 'processor', 
            'edition', 'box', 'lhr', 'plus', 'gold', 'bronze', 'wifi'
        ];
        
        foreach ($expectedWords as $word) {
            // Skip single characters (like 'a')
            if (strlen($word) < 2) continue;
            
            // Skip common spec words that stores might not include
            if (in_array($word, $ignoreList)) continue;
            
            // Skip standalone small numbers (like 14 cores, 3.5 GHz) to prevent false failures, 
            // but KEEP large identifying numbers (like 4080, 13600, 2021)
            if (is_numeric($word) && (int)$word < 1000) continue;
            
            // STRICT CHECK: If this significant word is completely missing from the store title, reject it
            if (strpos($scrapedClean, $word) === false) {
                return false;
            }
        }
        
        return true;
    }

    private function extractPriceNative(string $html, array $config, string $expectedName): float
    {
        if (empty($html)) return 0.0;

        $doc = new \DOMDocument();
        libxml_use_internal_errors(true);
        $doc->loadHTML($html);
        libxml_clear_errors();

        $xpath = new \DOMXPath($doc);
        $cards = $xpath->query($config['card_xpath']);
        
        if ($cards && $cards->length > 0) {
            for ($i = 0; $i < min(5, $cards->length); $i++) {
                $card = $cards->item($i);
                
                // Skip Out of Stock
                $cardText = strtolower($card->textContent);
                if (str_contains($cardText, 'out of stock') || str_contains($cardText, 'sold out')) {
                    continue;
                }
                
                // Title extraction
                $titleNodes = $xpath->query($config['title_xpath'], $card);
                $scrapedTitle = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : '';
                if (empty($scrapedTitle)) continue;

                // Pass through our new strict match function
                if (!$this->isStrictMatch($expectedName, $scrapedTitle)) {
                    continue; 
                }

                // Price extraction
                $priceNodes = $xpath->query($config['price_xpath'], $card);
                if ($priceNodes && $priceNodes->length > 0) {
                    $priceText = $priceNodes->item(0)->textContent;
                    $cleanString = preg_replace('/[^0-9.]/', '', $priceText);
                    $floatPrice = (float) $cleanString;

                    if ($floatPrice > 50) {
                        return $floatPrice;
                    }
                }
            }
        }

        return 0.0;
    }
}
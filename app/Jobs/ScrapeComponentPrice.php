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

    public $timeout = 180;

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
     * Exact word-boundary and modifier-aware comparison.
     * Prevents partial numeric matches and tier cross-pollination.
     */
    private function isStrictMatch(string $expected, string $scraped): bool
    {
        $expectedClean = strtolower(trim($expected));
        $scrapedClean  = strtolower(trim($scraped));

        // 1. Rejection List: Modifiers defining distinct product tiers/variants
        $tierModifiers = [
            'ti', 'super', 'xt', 'xtx', 'gre', 'ultra',
            'f', 'k', 'kf', 'ks', 'x3d'
        ];

        // 2. Reject scraped item if it contains a tier modifier NOT present in target
        foreach ($tierModifiers as $modifier) {
            $pattern = '/\b' . preg_quote($modifier, '/') . '\b/i';
            $inScraped  = (bool) preg_match($pattern, $scrapedClean);
            $inExpected = (bool) preg_match($pattern, $expectedClean);

            if ($inScraped && !$inExpected) {
                return false;
            }
        }

        // 3. Normalize non-alphanumeric characters to single spaces
        $normalizedExpected = preg_replace('/[^a-z0-9]+/i', ' ', $expectedClean);
        $normalizedScraped  = preg_replace('/[^a-z0-9]+/i', ' ', $scrapedClean);

        $expectedWords = array_unique(array_filter(explode(' ', $normalizedExpected)));

        $ignoreList = [
            'ghz', 'mhz', 'gb', 'tb', 'w', 'hz', 'core', 'processor', 
            'edition', 'box', 'lhr', 'plus', 'gold', 'bronze', 'wifi'
        ];

        // 4. Exact Word Boundary Check: Match tokens using \b boundaries
        foreach ($expectedWords as $word) {
            if (strlen($word) < 2 && !is_numeric($word)) continue;
            if (in_array($word, $ignoreList)) continue;

            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            if (!preg_match($pattern, $normalizedScraped)) {
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

                // Pass through strict match function
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
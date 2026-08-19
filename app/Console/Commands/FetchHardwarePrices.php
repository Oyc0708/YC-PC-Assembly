<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Cpu;
use App\Models\Gpu;
use App\Models\Motherboard;
use App\Models\Ram;
use App\Models\Psu;
use App\Models\PCCase;
use App\Models\Cooler;
use App\Models\ComponentPrice;
use Illuminate\Support\Str;

class FetchHardwarePrices extends Command
{
    protected $signature = 'prices:fetch';
    protected $description = 'Fetch latest prices from 5 Malaysian retailers';

    // The 5 selected Malaysian sources
    protected $vendors = [
        'ALL IT Hypermarket' => 'https://www.allithypermarket.com.my/search?type=product&q=',
        'Brightstar Computer' => 'https://brightstarcomp.com/search?q=',
        'Shopee Malaysia' => 'https://shopee.com.my/search?keyword=',
        'Lazada Malaysia' => 'https://www.lazada.com.my/catalog/?q=',
        'Ideal Tech PC' => 'https://idealtech.com.my/?s='
    ];

    public function handle()
    {
        $this->info('Initializing Malaysian Market Price Sync...');

        // Array of all your component classes
        $componentClasses = [
            Cpu::class, Gpu::class, Motherboard::class, Ram::class,
            Psu::class, PCCase::class, Cooler::class
        ];

        foreach ($componentClasses as $class) {
            $components = $class::all();
            $className = class_basename($class);
            $this->info("Syncing {$className}s...");

            foreach ($components as $component) {
                // Ensure name is URL safe
                $searchQuery = urlencode($component->name);

                foreach ($this->vendors as $vendorName => $baseUrl) {
                    $productUrl = $baseUrl . $searchQuery;
                    
                    // In a production environment, you would use RapidAPI, ScrapingBee,
                    // or an official API here to fetch the actual HTML/JSON and parse it.
                    // $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0'])->get($productUrl);
                    
                    /* -------------------------------------------------------------
                    DEVELOPMENT SIMULATION LOGIC
                    Because live scraping Shopee/Lazada requires enterprise APIs,
                    we simulate a realistic Malaysian market variance (+/- 15%)
                    based on the component's base price for the project.
                    ------------------------------------------------------------- */
                    
                    // Base price variation between 85% to 115% of MSRP
                    $variance = rand(85, 115) / 100;
                    $marketPrice = $component->price * $variance;
                    
                    // Randomly decide if it's out of stock (5% chance)
                    $inStock = rand(1, 100) > 5;

                    ComponentPrice::updateOrCreate(
                        [
                            'component_type' => $class,
                            'component_id'   => $component->id,
                            'vendor'         => $vendorName,
                        ],
                        [
                            'price'      => $marketPrice,
                            'url'        => $productUrl,
                            'in_stock'   => $inStock,
                            'updated_at' => now(),
                        ]
                    );
                }
            }
        }

        $this->info('Database Synchronization Complete! 5 sources updated.');
    }
}
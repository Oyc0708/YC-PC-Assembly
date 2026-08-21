<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Jobs\ScrapeComponentPrice;
use App\Models\{Cpu, Gpu, Motherboard, Ram, Psu, PCCase, Cooler, Storage};

class FetchHardwarePrices extends Command
{
    protected $signature = 'prices:fetch {--force : Bypass 24-hour cache filter}';
    protected $description = 'Dispatch hardware price scraping tasks to background queue workers.';

    public function handle()
    {
        $this->info('Scanning database for components needing sync...');

        $componentClasses = [
            Cpu::class, Gpu::class, Motherboard::class, Ram::class,
            Psu::class, PCCase::class, Cooler::class, Storage::class
        ];

        $force = $this->option('force');
        $totalQueued = 0;

        foreach ($componentClasses as $class) {
            $className = class_basename($class);

            // OPTION 2: 24-Hour Cache Check
            $query = $class::query();
            if (!$force) {
                $query->where('updated_at', '<', now()->subHours(24))
                    ->orWhereNull('updated_at');
            }

            $components = $query->get();

            if ($components->isEmpty()) {
                $this->line(" ⏩ [{$className}s] All items recently updated. Skipping...");
                continue;
            }

            // OPTION 3: Dispatch each item as a background job
            foreach ($components as $component) {
                ScrapeComponentPrice::dispatch($component, $class);
                $totalQueued++;
            }

            $this->info(" Queued {$components->count()} {$className}(s) for background processing.");
        }

        $this->info("\n Done! Queued {$totalQueued} items for processing.");
    }
}
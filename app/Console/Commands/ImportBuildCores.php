<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Cpu;
use App\Models\Gpu;
use App\Models\Ram;
use App\Models\Motherboard;
use App\Models\Psu;
use App\Models\PcCase;
use App\Models\Cooler;
use App\Models\Storage;

class ImportBuildCores extends Command
{
    protected $signature = 'import:buildcores';
    protected $description = 'Import modern components from BuildCores OpenDB into MySQL';

    /**
     * Categorized blocklist for obsolete hardware standards
     */
    protected array $legacyFilters = [
        'CPU' => [
            'Pentium', 'Celeron', 'Core 2', 'Athlon', 'Sempron', 'Phenom', 
            ' FX-', ' A4-', ' A6-', ' A8-', ' A10-',
            'i3-2', 'i3-3', 'i3-4', 'i3-5', 'i3-6', 'i3-7',
            'i5-2', 'i5-3', 'i5-4', 'i5-5', 'i5-6', 'i5-7',
            'i7-2', 'i7-3', 'i7-4', 'i7-5', 'i7-6', 'i7-7',
            'LGA 775', 'LGA 1156', 'LGA 1155', 'LGA 1150', 'LGA 1366', 'AM2', 'AM3', 'FM1', 'FM2'
        ],
        'GPU' => [
            'GeForce 2', 'GeForce 3', 'GeForce 4', 'GeForce 5', 'GeForce 6', 'GeForce 7', 'GeForce 8', 'GeForce 9',
            'GTX 2', 'GTX 4', 'GTX 5', 'GTX 6', 'GTX 7',
            'Radeon HD', 'R5 2', 'R7 2', 'R7 3', 'R9 2', 'R9 3', 'FirePro', 'Voodoo', 'AGP'
        ],
        'RAM' => [
            'DDR2', 'DDR3', 'DDR ', 'SDRAM'
        ],
        'Motherboard' => [
            'LGA 775', 'LGA 1156', 'LGA 1155', 'LGA 1150', 'LGA 1366', 'AM2', 'AM3', 'FM1', 'FM2',
            'DDR2', 'DDR3', 'Z77', 'Z87', 'Z97', 'H61', 'H81', 'B75', 'B85'
        ],
        'PSU' => [
            'ATX12V v1', '20-pin'
        ],
        'PCCase' => [
            'BTX Form Factor'
        ],
        'CPUCooler' => [
            'Socket 478', 'Socket 754', 'Socket 939', 'LGA 775'
        ],
        // --- ADDED STORAGE BLOCKLIST ---
        'Storage' => [
            'IDE', 'PATA', 'SATA 3Gb/s', 'SATA II', '5400RPM'
        ]
    ];

    public function handle()
    {
        $basePath = storage_path('app/buildcores-open-db/open-db');

        if (!File::exists($basePath)) {
            $this->error('The OpenDB repository was not found. Please clone it into storage/app first.');
            return;
        }

        // Map directories to their Models and extraction logic
        $categories = [
            'CPU' => function($data) {
                Cpu::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'cores' => $data['cores']['total'] ?? 'Unknown',
                    'threads' => $data['threads']['total'] ?? 'Unknown',
                    'socket' => $data['socket'] ?? 'Unknown',
                    'tdp' => $data['tdp'] ?? 'Unknown',
                    'base_clock' => $data['clocks']['performance']['base'] ?? 'Unknown',
                    'price' => 0,
                ]);
            },
            'GPU' => function($data) {
                Gpu::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'vram_gb' => $data['memory'] ?? 'Unknown',
                    'tdp' => $data['tdp'] ?? 'Unknown',
                    'memory_type' => $data['memory_type'] ?? 'Unknown',
                    'length_mm' => $data['dimensions']['length_mm'] ?? 'Unknown',
                    'score' => 0,
                    'price' => 0,
                ]);
            },
            'RAM' => function($data) {
                Ram::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'type' => $data['ram_type'] ?? 'Unknown',
                    'capacity_gb' => $data['capacity'] ?? 'Unknown',
                    'speed' => $data['speed'] ?? 'Unknown',
                    'score' => 0,
                    'price' => 0,
                ]);
            },
            'Motherboard' => function($data) {
                Motherboard::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'socket' => $data['socket'] ?? 'Unknown',
                    'ram_type' => $data['memory']['ram_type'] ?? 'Unknown',
                    'form_factor' => $data['form_factor'] ?? 'Unknown',
                    'price' => 0,
                ]);
            },
            'PSU' => function($data) {
                Psu::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'wattage' => $data['wattage'] ?? 'Unknown',
                    'efficiency' => $data['efficiency_rating'] ?? 'Unknown',
                    'modular' => $data['modular'] ?? 'false',
                    'price' => 0,
                ]);
            },
            'PCCase' => function($data) {
                PcCase::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'max_gpu_length_mm' => $data['max_video_card_length'] ?? 'Unknown',
                    'form_factor' => $data['form_factor'] ?? 'Unknown',
                    'price' => 0,
                ]);
            },
            'CPUCooler' => function($data) {
                Cooler::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'max_tdp' => $data['tdp'] ?? 'Unknown',
                    'is_water_cooled' => isset($data['water_cooled']) ? ($data['water_cooled'] === true ? 'true' : 'false') : 'false',
                    'price' => 0,
                ]);
            },
            'Storage' => function($data) {
                Storage::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'type' => $data['type'] ?? 'Unknown', // SSD or HDD
                    'capacity' => $data['capacity'] ?? 'Unknown', // Usually in GB based on BuildCores standard
                    'form_factor' => $data['form_factor'] ?? 'Unknown',
                    'interface' => $data['interface'] ?? 'Unknown',
                    'nvme' => isset($data['nvme']) ? ($data['nvme'] === true ? 'true' : 'false') : 'false',
                    'price' => 0,
                ]);
            },
        ];

        foreach ($categories as $folder => $parser) {
            $folderPath = $basePath . '/' . $folder;
            
            if (!File::exists($folderPath)) {
                $this->warn("Skipping {$folder}: Directory not found.");
                continue;
            }

            $this->info("Importing category: {$folder}...");
            $files = File::files($folderPath);
            $bar = $this->output->createProgressBar(count($files));
            $bar->start();

            $skippedCount = 0;

            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file->getPathname()), true);
                
                if ($data && isset($data['opendb_id'])) {
                    // Check if component matches legacy blocklist
                    if ($this->isLegacyComponent($data, $folder)) {
                        $skippedCount++;
                    } else {
                        $parser($data); // Import valid component
                    }
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
            $this->comment("  -> Skipped {$skippedCount} legacy item(s) in {$folder}.");
        }

        $this->info('Master Import Completed Successfully!');
    }

    /**
     * Inspects JSON data against the legacy blocklist for that category.
     */
    private function isLegacyComponent(array $data, string $folder): bool
    {
        $name = $data['metadata']['name'] ?? '';
        $socket = $data['socket'] ?? '';
        $ramType = $data['type'] ?? $data['memory']['type'] ?? '';
        
        // Include 'interface' and 'form_factor' to easily catch legacy storage drives
        $interface = $data['interface'] ?? '';
        $formFactor = $data['form_factor'] ?? '';

        // Combine metadata text to scan in one pass
        $searchableText = strtolower("{$name} {$socket} {$ramType} {$interface} {$formFactor}");

        $filters = $this->legacyFilters[$folder] ?? [];

        foreach ($filters as $keyword) {
            if (str_contains($searchableText, strtolower($keyword))) {
                return true; // Marked as legacy
            }
        }

        return false;
    }
}
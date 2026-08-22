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
            'DDR2', 'DDR3', 'SDRAM'
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

        $categories = [
            'CPU' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                $baseClock = $this->sanitizeFloat(
                    $data['clocks']['performance']['base']
                    ?? $data['clocks']['base']
                    ?? $data['base_clock']
                    ?? 0
                );
                
                $boostClock = $this->sanitizeFloat(
                    $data['clocks']['performance']['boost']
                    ?? $data['clocks']['boost']
                    ?? $data['boost_clock']
                    ?? $baseClock
                );

                Cpu::updateOrCreate(
                    ['id' => $data['opendb_id']], 
                    [
                        'name' => $name, 
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'cores' => $this->sanitizeInt($data['cores']['total'] ?? $data['cores'] ?? 0),
                        'threads' => $this->sanitizeInt($data['threads']['total'] ?? $data['threads'] ?? 0),
                        'socket' => $this->sanitizeString($data['socket'] ?? 'Unknown'),
                        'has_igpu' => !empty($data['integrated_graphics']), // [CRITICAL FIX] Map the iGPU data here
                        'tdp' => $this->sanitizeInt($data['tdp'] ?? 0),
                        'base_clock' => $baseClock,
                        'boost_clock' => $boostClock > 0 ? $boostClock : $baseClock,
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'GPU' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                Gpu::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'memory' => $this->sanitizeInt($data['memory'] ?? $data['vram_gb'] ?? 0),
                        'tdp' => $this->sanitizeInt($data['tdp'] ?? 0),
                        'memory_type' => $this->sanitizeString($data['memory_type'] ?? 'Unknown'),
                        'length_mm' => $this->sanitizeInt($data['dimensions']['length_mm'] ?? $data['length_mm'] ?? 0),
                        'score' => $this->sanitizeInt($data['score'] ?? 0),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'RAM' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                Ram::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'type' => $this->sanitizeString($data['ram_type'] ?? $data['type'] ?? 'Unknown'),
                        'capacity' => $this->sanitizeInt($data['capacity'] ?? 0),
                        'speed' => $this->sanitizeInt($data['speed'] ?? 0),
                        'score' => $this->sanitizeInt($data['score'] ?? 0),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'Motherboard' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                Motherboard::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'socket' => $this->sanitizeString($data['socket'] ?? 'Unknown'),
                        'ram_type' => $this->sanitizeString($data['memory']['ram_type'] ?? $data['ram_type'] ?? 'Unknown'),
                        'form_factor' => $this->sanitizeString($data['form_factor'] ?? 'Unknown'),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'PSU' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                Psu::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'wattage' => $this->sanitizeInt($data['wattage'] ?? 0),
                        'efficiency' => $this->sanitizeString($data['efficiency_rating'] ?? $data['efficiency'] ?? 'Unknown'),
                        'modular' => $this->sanitizeBool($data['modular'] ?? false),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'PCCase' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                PcCase::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'max_gpu_length_mm' => $this->sanitizeInt($data['max_video_card_length'] ?? $data['max_gpu_length_mm'] ?? 0),
                        'form_factor' => $this->sanitizeString($data['form_factor'] ?? 'Unknown'),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'CPUCooler' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                Cooler::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'max_tdp' => $this->sanitizeInt($data['tdp'] ?? $data['max_tdp'] ?? 0),
                        'is_water_cooled' => $this->sanitizeBool($data['water_cooled'] ?? $data['is_water_cooled'] ?? false),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
            },
            'Storage' => function($data) {
                $name = $this->sanitizeString($data['metadata']['name'] ?? null);
                if ($name === 'Unknown') return;

                Storage::updateOrCreate(
                    ['id' => $data['opendb_id']],
                    [
                        'name' => $name,
                        'manufacturer' => $this->sanitizeString($data['metadata']['manufacturer'] ?? 'Unknown'),
                        'type' => $this->sanitizeString($data['type'] ?? 'Unknown'),
                        'capacity' => $this->sanitizeInt($data['capacity'] ?? 0),
                        'form_factor' => $this->sanitizeString($data['form_factor'] ?? 'Unknown'),
                        'interface' => $this->sanitizeString($data['interface'] ?? 'Unknown'),
                        'nvme' => $this->sanitizeBool($data['nvme'] ?? false),
                        'price' => $this->sanitizeFloat($data['price'] ?? 0),
                    ]
                );
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
                    if ($this->isLegacyComponent($data, $folder)) {
                        $skippedCount++;
                    } else {
                        $parser($data);
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

    private function isLegacyComponent(array $data, string $folder): bool
    {
        $name = $data['metadata']['name'] ?? '';
        $socket = $data['socket'] ?? '';
        $ramType = $data['type'] ?? $data['memory']['type'] ?? $data['ram_type'] ?? '';
        $interface = $data['interface'] ?? '';
        $formFactor = $data['form_factor'] ?? '';

        $searchableText = "{$name} {$socket} {$ramType} {$interface} {$formFactor}";
        $filters = $this->legacyFilters[$folder] ?? [];

        foreach ($filters as $keyword) {
            $pattern = '/\b' . preg_quote(trim($keyword), '/') . '(?!\+)/i';
            if (preg_match($pattern, $searchableText)) {
                return true;
            }
        }

        return false;
    }

    private function sanitizeInt(mixed $value, int $default = 0): int
    {
        if (is_numeric($value)) {
            return (int) $value;
        }
        if (is_string($value) && preg_match('/(\d+)/', $value, $matches)) {
            return (int) $matches[1];
        }
        return $default;
    }

    private function sanitizeFloat(mixed $value, float $default = 0.0): float
    {
        if (is_numeric($value)) {
            return (float) $value;
        }
        if (is_string($value) && preg_match('/(\d+(?:\.\d+)?)/', $value, $matches)) {
            return (float) $matches[1];
        }
        return $default;
    }

    private function sanitizeBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        if (is_string($value)) {
            return in_array(strtolower(trim($value)), ['true', '1', 'yes']);
        }
        return (bool) $value;
    }

    private function sanitizeString(mixed $value, int $maxLength = 100): string
    {
        if (!is_string($value) || empty(trim($value))) {
            return 'Unknown';
        }

        return mb_substr(trim($value), 0, $maxLength, 'UTF-8');
    }
}
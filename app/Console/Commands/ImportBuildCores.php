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

class ImportBuildCores extends Command
{
    protected $signature = 'import:buildcores';
    protected $description = 'Import all categories from BuildCores OpenDB into MySQL';

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
                    'cores' => $data['cores']['total'] ?? 0,
                    'socket' => $data['socket'] ?? 'Unknown',
                    'tdp' => $data['tdp'] ?? null,
                    'base_clock' => $data['clocks']['performance']['base'] ?? null,
                    'price' => rand(300, 1500),
                ]);
            },
            'GPU' => function($data) {
                Gpu::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'vram_gb' => $data['memory']['capacity_gb'] ?? null,
                    'tdp' => $data['tdp'] ?? null,
                    'length_mm' => $data['dimensions']['length_mm'] ?? 240, // Fallback safe length
                    'score' => rand(60, 100),
                    'price' => rand(800, 4000),
                ]);
            },
            'RAM' => function($data) {
                Ram::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'type' => $data['type'] ?? 'DDR4',
                    'capacity_gb' => $data['modules']['capacity_gb'] ?? 8,
                    'speed' => $data['speed'] ?? 3200,
                    'score' => rand(50, 90),
                    'price' => rand(150, 600),
                ]);
            },
            'Motherboard' => function($data) {
                Motherboard::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'socket' => $data['socket'] ?? 'Unknown',
                    'ram_type' => $data['memory']['type'] ?? 'DDR4',
                    'form_factor' => $data['form_factor'] ?? 'ATX',
                    'price' => rand(300, 1200),
                ]);
            },
            'PSU' => function($data) {
                Psu::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'wattage' => $data['wattage'] ?? 500,
                    'efficiency' => $data['efficiency_rating'] ?? '80+',
                    'price' => rand(200, 800),
                ]);
            },
            'PCCase' => function($data) {
                PcCase::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'max_gpu_length_mm' => $data['clearances']['gpu_length_mm'] ?? 320,
                    'form_factor' => $data['form_factor'] ?? 'ATX Mid Tower',
                    'price' => rand(150, 600),
                ]);
            },
            'CPUCooler' => function($data) {
                Cooler::updateOrCreate(['id' => $data['opendb_id']], [
                    'name' => $data['metadata']['name'] ?? 'Unknown',
                    'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                    'max_tdp' => $data['tdp'] ?? 150,
                    'is_water_cooled' => isset($data['radiator']),
                    'price' => rand(80, 400),
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

            foreach ($files as $file) {
                $data = json_decode(file_get_contents($file->getPathname()), true);
                if ($data && isset($data['opendb_id'])) {
                    $parser($data); // Execute the mapped logic closure
                }
                $bar->advance();
            }

            $bar->finish();
            $this->newLine();
        }

        $this->info('Master Import Completed Successfully!');
    }
}
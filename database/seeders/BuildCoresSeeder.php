<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\{Cpu, Gpu, Motherboard, Ram, Cooler, Psu, PcCase};

class BuildCoresSeeder extends Seeder
{
    public function run()
    {
        $basePath = storage_path('app/buildcores-open-db/open-db');

        if (!File::exists($basePath)) {
            $this->command->error("Database not found at {$basePath}");
            return;
        }

        $this->command->info('Starting database population from local JSON files...');

        $this->seedCpus("$basePath/CPU");
        $this->seedGpus("$basePath/GPU");
        $this->seedMotherboards("$basePath/Motherboard");
        $this->seedRams("$basePath/RAM");
        $this->seedCoolers("$basePath/CPUCooler");
        $this->seedPsus("$basePath/PSU");
        $this->seedCases("$basePath/PCCase");

        $this->command->info('All hardware categories successfully populated!');
    }

    private function getFullName($data)
    {
        $manufacturer = $data['metadata']['manufacturer'] ?? 'Unknown';
        $series = $data['metadata']['series'] ?? '';
        $variant = $data['metadata']['variant'] ?? '';
        return trim("{$manufacturer} {$series} {$variant}");
    }

    private function saveComponent($modelClass, $name, $attributes)
    {
        $component = $modelClass::firstOrNew(['name' => $name]);

        if (!$component->exists) {
            $component->id = (string) Str::uuid();
        }

        foreach ($attributes as $key => $value) {
            $component->{$key} = $value;
        }

        $component->save();
    }

    private function seedCpus($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping CPUs.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(Cpu::class, $this->getFullName($data), [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'cores'        => $data['cores']['performance'] ?? $data['cores']['total'] ?? 4,
                'socket'       => $data['socket'] ?? 'Unknown',
                // FIXED: Look inside the 'specifications' array for TDP
                'tdp'          => $data['specifications']['tdp'] ?? 65,
                'base_clock'   => $data['clocks']['performance']['base'] ?? 3.0,
                'boost_clock'  => $data['clocks']['performance']['boost'] ?? $data['clocks']['performance']['base'] ?? 3.5,
                'price'        => rand(400, 2500) // Placeholder
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} CPUs.");
    }

    private function seedGpus($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping GPUs.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(Gpu::class, $this->getFullName($data), [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                // FIXED: Mapping directly to the root level keys
                'length_mm'    => $data['length'] ?? 240,
                'tdp'          => $data['tdp'] ?? 150,
                'memory'       => $data['memory'] ?? 8,
                // We'll prioritize the boost clock for performance estimation, falling back to base
                'clock_speed'  => $data['core_boost_clock'] ?? $data['core_base_clock'] ?? 1500,
                'price'        => rand(800, 6000) // Placeholder
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} GPUs.");
    }

    private function seedMotherboards($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping Motherboards.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(Motherboard::class, $this->getFullName($data), [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'socket'       => $data['socket'] ?? 'Unknown',
                // FIXED: The key inside the memory array is 'ram_type', not 'type'
                'ram_type'     => $data['memory']['ram_type'] ?? 'DDR4',
                // ADDED: Extracting capacity and slots for the Comparison UI
                'max_ram'      => $data['memory']['max'] ?? 64,
                'ram_slots'    => $data['memory']['slots'] ?? 4,
                'form_factor'  => $data['form_factor'] ?? 'ATX',
                'price'        => rand(300, 1500)
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} Motherboards.");
    }

    private function seedRams($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping RAM.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(Ram::class, $this->getFullName($data), [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                // FIXED: The JSON key is 'ram_type', mapping it to your DB's 'type' column
                'type'         => $data['ram_type'] ?? 'DDR4',
                'capacity'     => $data['capacity'] ?? 16,
                'speed'        => $data['speed'] ?? 3200,
                'price'        => rand(150, 800)
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} RAM kits.");
    }

    private function seedCoolers($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping Coolers.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(Cooler::class, $this->getFullName($data), [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                // Many coolers in this DB lack a max_tdp key, so the 150W fallback is vital here
                'max_tdp'      => $data['max_tdp'] ?? 150,
                // Optional: Capturing sockets as a JSON string for future ValidationEngine checks
                // 'sockets'   => isset($data['cpu_sockets']) ? json_encode($data['cpu_sockets']) : json_encode([]),
                'price'        => rand(100, 600)
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} Coolers.");
    }

    private function seedPsus($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping PSUs.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(Psu::class, $this->getFullName($data), [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                // Wattage is correctly at the root level!
                'wattage'      => $data['wattage'] ?? 500,
                // Optional extras for your UI
                // 'efficiency'   => $data['efficiency_rating'] ?? 'Unrated',
                // 'modular'      => $data['modular'] ?? 'Unknown',
                'price'        => rand(200, 1000)
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} PSUs.");
    }

    private function seedCases($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping Cases.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $this->saveComponent(PcCase::class, $this->getFullName($data), [
                'manufacturer'      => $data['metadata']['manufacturer'] ?? 'Unknown',
                // FIXED: The key is 'max_video_card_length', not hidden inside a 'clearance' array
                'max_gpu_length_mm' => $data['max_video_card_length'] ?? 300, 
                'form_factor'       => $data['form_factor'] ?? 'ATX',
                // Optional: Great for filtering in the UI later
                // 'side_panel'     => $data['side_panel'] ?? 'Solid',
                'price'             => rand(150, 800)
            ]);
            $count++;
        }
        $this->command->info("Seeded {$count} PC Cases.");
    }
}
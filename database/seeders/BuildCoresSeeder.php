<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use App\Models\{Cpu, Gpu, Motherboard, Ram, Cooler, Psu, PcCase, Storage};

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
        $this->seedStorages("$basePath/Storage");

        $this->command->info('All hardware categories successfully populated!');
    }

    private function getFullName($data)
    {
        $manufacturer = $data['metadata']['manufacturer'] ?? '';
        $series = $data['metadata']['series'] ?? '';
        $variant = $data['metadata']['variant'] ?? '';
        
        // Combine all parts
        $name = "{$manufacturer} {$series} {$variant}";
        
        // Remove double/triple spaces that occur when a middle variable is missing
        $name = preg_replace('/\s+/', ' ', $name);
        
        return mb_substr($name, 0, 190);
    }

    private function saveComponent($modelClass, $name, $attributes)
    {
        try {
            $safeName = mb_substr(trim($name), 0, 190);
            $component = $modelClass::firstOrNew(['name' => $safeName]);

            if (!$component->exists) {
                $component->id = (string) Str::uuid();
                
                foreach ($attributes as $key => $value) {
                    $component->{$key} = $value;
                }
                
                $component->name = $safeName;
                $component->save();
                
                return true; // Successfully saved a new item
            }
            return false; // Skipped because it already exists
        } catch (\Illuminate\Database\QueryException $e) {
            if ($e->getCode() == 23000) {
                return false; // Skipped because of a database-level duplicate
            }
            throw $e;
        }
    }

    private function seedCpus($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping CPUs.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $fullName = $this->getFullName($data);
            if ($this->isObsolete('cpu', $fullName, $data)) continue;

            if ($this->saveComponent(Cpu::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'cores'        => $data['cores']['total'],
                'socket'       => $data['socket'] ?? 'Unknown',
                'tdp'          => $data['specifications']['tdp'] ?? 65,
                'base_clock'   => $data['clocks']['performance']['base'] ?? 3.0,
                'price'        => 0
            ])) {
                $count++;
            }
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

            $fullName = $this->getFullName($data);
            if ($this->isObsolete('gpu', $fullName, $data)) continue;

            if ($this->saveComponent(Gpu::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'length_mm'    => $data['length'] ?? 240,
                'tdp'          => $data['tdp'] ?? 150,
                'memory'       => $data['memory'] ?? 8,
                'memory_type'  => $data['memory_type'] ?? 'GDDR6',
                'clock_speed'  => $data['core_boost_clock'] ?? $data['core_base_clock'] ?? 1500,
                'price'        => 0,
            ])) {
                $count++;
            }
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

            $fullName = $this->getFullName($data);
            if ($this->isObsolete('mobo', $fullName, $data)) continue;

            if ($this->saveComponent(Motherboard::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'socket'       => $data['socket'] ?? 'Unknown',
                'ram_type'     => $data['memory']['ram_type'] ?? 'DDR4',
                'max_ram'      => $data['memory']['max'] ?? 64,
                'ram_slots'    => $data['memory']['slots'] ?? 4,
                'form_factor'  => $data['form_factor'] ?? 'ATX',
                'price'        => 0,
            ])) {
                $count++;
            }
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

            $fullName = $this->getFullName($data);
            if ($this->isObsolete('ram', $fullName, $data)) continue;

            if ($this->saveComponent(Ram::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'type'         => $data['ram_type'] ?? 'DDR4',
                'capacity'     => $data['capacity'] ?? 16,
                'speed'        => $data['speed'] ?? 3200,
                'price'        => 0,
            ])) {
                $count++;
            }
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

            $fullName = $this->getFullName($data);

            if ($this->saveComponent(Cooler::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'max_tdp'      => $data['max_tdp'] ?? random_int(150, 1000),
                'price'        => 0,
            ])) {
                $count++;
            }
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

            $fullName = $this->getFullName($data);

            if ($this->saveComponent(Psu::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'wattage'      => $data['wattage'] ?? 500,
                'price'        => 0,
            ])) {
                $count++;
            }
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

            $fullName = $this->getFullName($data);

            if ($this->saveComponent(PcCase::class, $fullName, [
                'manufacturer'      => $data['metadata']['manufacturer'] ?? 'Unknown',
                'max_gpu_length_mm' => $data['max_video_card_length'] ?? 300,
                'form_factor'       => $data['form_factor'] ?? 'ATX',
                'price'             => 0,
            ])) {
                $count++;
            }
        }
        $this->command->info("Seeded {$count} PC Cases.");
    }

    private function seedStorages($path)
    {
        if (!File::exists($path)) return $this->command->warn("Skipping Storage.");
        
        $count = 0;
        foreach (File::files($path) as $file) {
            $data = json_decode(File::get($file->getPathname()), true);
            if (!$data) continue;

            $fullName = $this->getFullName($data);
            if ($this->isObsolete('storage', $fullName, $data)) continue;

            if ($this->saveComponent(Storage::class, $fullName, [
                'manufacturer' => $data['metadata']['manufacturer'] ?? 'Unknown',
                'type'         => $data['type'] ?? 'Unknown', // e.g., SSD, HDD
                'capacity'     => $data['capacity'] ?? 500,
                'form_factor'  => $data['form_factor'] ?? 'Unknown',
                'interface'    => $data['interface'] ?? 'Unknown',
                'nvme'         => isset($data['nvme']) ? (($data['nvme'] === true || $data['nvme'] === 'true') ? 'true' : 'false') : 'false',
                'price'        => 0,
            ])) {
                $count++;
            }
        }
        $this->command->info("Seeded {$count} Storage Drives.");
    }

    /**
     * Helper method to filter out obsolete or irrelevant components.
     */
    private function isObsolete($category, $name, $data = [])
    {
        $name = strtolower($name);

        switch (strtolower($category)) {
            case 'gpu':
                $oldGpus = ['geforce 256', 'gtx 4', 'gtx 5', 'gtx 6', 'gtx 7', 'gtx 9', 'radeon hd', 'r7 ', 'r9 ', 'agp'];
                return Str::contains($name, $oldGpus);

            case 'cpu':
                $oldCpus = ['core 2', 'pentium', 'celeron', 'fx-', 'athlon', 'a-series'];
                // Blocks Intel 1st-7th gen and Ryzen 1000 series
                $regex = '/(i[3579]-[234567]\d{3})|(ryzen\s[3579]\s1\d{2}0)/';
                return Str::contains($name, $oldCpus) || preg_match($regex, $name);

            case 'ram':
                $type = $data['ram_type'] ?? '';
                // Skip DDR, DDR2, DDR3
                return in_array(strtoupper($type), ['DDR', 'DDR2', 'DDR3']);

            case 'storage':
                $capacity = $data['capacity'] ?? 9999;
                // Skip drives smaller than 250GB
                return $capacity < 250;

            case 'mobo':
                $oldSockets = ['lga775', 'lga1150', 'lga1155', 'lga1156', 'am3', 'am3+', 'fm2'];
                $socket = strtolower($data['socket'] ?? '');
                return in_array($socket, $oldSockets);
        }

        return false;
    }
}
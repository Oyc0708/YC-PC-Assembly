<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Cpu;

class ImportIcecatHardware extends Command
{
    // The command you will type in your terminal
    protected $signature = 'import:hardware';
    protected $description = 'Fetch hardware specs from Open Icecat and populate the database';

    public function handle()
    {
        $this->info('Starting Open Icecat Import...');

        $username = 'YOUR_ICECAT_USERNAME';
        
        // Example: Fetching a specific Intel CPU using the Icecat JSON API
        // In reality, you would likely parse an Icecat CSV/XML index file and loop through multiple product codes
        $brand = 'Intel';
        $productCode = 'BX8071512100F'; // Intel Core i3-12100F product code

        // Make the API request
        $response = Http::withBasicAuth($username, 'YOUR_ICECAT_PASSWORD')
            ->get("https://live.icecat.biz/api/?shopname={$username}&lang=en&Brand={$brand}&ProductCode={$productCode}");

        if ($response->successful()) {
            $data = $response->json();
            
            // Extract the relevant specs from Icecat's structured data
            // Note: You will need to map Icecat's specific spec IDs to your database columns
            $cpuName = $data['data']['GeneralInfo']['Title'];
            
            // Insert into your local database
            Cpu::updateOrCreate(
                ['name' => $cpuName], // Prevent duplicates
                [
                    'price' => rand(300, 1500), // Mocking price as Icecat does not provide it
                    'socket' => 'LGA1700', // You would parse this from the Icecat 'Features' array
                    'tdp' => 65,           // Parse from 'Features'
                    'score' => rand(50, 100) // Mocking your custom benchmark score
                ]
            );

            $this->info("Successfully imported: {$cpuName}");
        } else {
            $this->error('Failed to fetch data from Open Icecat.');
        }
    }
}
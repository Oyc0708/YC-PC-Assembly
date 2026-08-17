<?php

namespace App\Services;

class ValidationEngine
{
    /**
     * Run all hardware constraint checks against the current build session.
     */
    public function runChecks(array $build): array
    {
        $issues = [];
        $warnings = [];
        
        // Base wattage for motherboard, fans, and storage
        $estWattage = 50; 

        // Safely extract parts from the array
        $cpu = $build['cpu'] ?? null;
        $mobo = $build['mobo'] ?? null;
        $ram = $build['ram'] ?? null;
        $gpu = $build['gpu'] ?? null;
        $cooler = $build['cooler'] ?? null;
        $case = $build['case'] ?? null;
        $psu = $build['psu'] ?? null;

        // 1. CPU & Motherboard Socket Compatibility
        if ($cpu && $mobo) {
            // strcasecmp compares strings without case sensitivity
            if (strcasecmp($cpu->socket, $mobo->socket) !== 0) {
                $issues[] = "Socket Mismatch: The {$cpu->name} uses an {$cpu->socket} socket, but the {$mobo->name} requires an {$mobo->socket} socket.";
            }
        }

        // 2. Motherboard & RAM Generation Compatibility
        if ($mobo && $ram) {
            if (strcasecmp($mobo->ram_type, $ram->type) !== 0) {
                $issues[] = "Memory Mismatch: The {$mobo->name} supports {$mobo->ram_type} RAM, but you selected {$ram->type} modules.";
            }
        }

        // 3. CPU TDP vs Cooler Thermal Capacity
        if ($cpu && $cooler) {
            // Require a 10% buffer for thermal headroom
            $requiredThermalCapacity = $cpu->tdp * 1.1;
            if ($cooler->max_tdp < $requiredThermalCapacity) {
                $warnings[] = "Thermal Limit: The {$cooler->name} (Max TDP: {$cooler->max_tdp}W) may struggle to cool the {$cpu->name} (TDP: {$cpu->tdp}W) under heavy load.";
            }
        }

        // 4. GPU Length vs Case Physical Clearance
        if ($gpu && $case) {
            if ($gpu->length_mm > $case->max_gpu_length_mm) {
                $issues[] = "Clearance Error: The {$gpu->name} is {$gpu->length_mm}mm long, but the {$case->name} only supports GPUs up to {$case->max_gpu_length_mm}mm.";
            }
        }

        // 5. System Power Draw vs PSU Wattage Output
        if ($cpu) $estWattage += $cpu->tdp ?? 65;
        if ($gpu) $estWattage += $gpu->tdp ?? 0;

        if ($psu) {
            // Recommend a 20% power buffer for transient spikes
            $recommendedWattage = $estWattage * 1.2;
            
            if ($psu->wattage < $estWattage) {
                $issues[] = "Power Deficit: Your system draws an estimated {$estWattage}W, but the {$psu->name} only provides {$psu->wattage}W. The system may shut down under load.";
            } elseif ($psu->wattage < $recommendedWattage) {
                $warnings[] = "Power Headroom: Your {$psu->name} ({$psu->wattage}W) barely meets the requirement of {$estWattage}W. A PSU with at least " . (int)$recommendedWattage . "W is recommended for stability.";
            }
        }

        return [
            'is_valid'          => count($issues) === 0,
            'issues'            => $issues,
            'warnings'          => $warnings,
            'estimated_wattage' => $estWattage
        ];
    }

    public function calculateScores(array $build): array
    {
        // Safely extract scores, default to 0 if part is missing or doesn't have a score
        $cpuScore = $build['cpu']->score ?? 0;
        $gpuScore = $build['gpu']->score ?? 0;
        $ramScore = $build['ram']->score ?? 0;

        // Apply prototype weighting formula
        $gaming = (int) round(($gpuScore * 0.65 + $cpuScore * 0.25 + $ramScore * 0.10) * 115);
        $productivity = (int) round(($cpuScore * 0.50 + $ramScore * 0.30 + $gpuScore * 0.20) * 115);
        $overall = (int) round(($gaming * 0.55) + ($productivity * 0.45));

        // Determine performance tier
        $tier = 'Entry-Level (1080p Low)';
        if ($gaming >= 10500) $tier = 'God Tier (4K Ultra)';
        elseif ($gaming >= 8800) $tier = 'Enthusiast (1440p Ultra)';
        elseif ($gaming >= 7200) $tier = 'High Performance (1440p High)';
        elseif ($gaming >= 5500) $tier = 'Mid-Range (1080p High)';

        // Calculate percentage capped at 100% (11500 is max theoretical score)
        $percentage = min(100, ($overall / 11500) * 100);

        return [
            'gaming'       => $gaming,
            'productivity' => $productivity,
            'overall'      => $overall,
            'tier'         => $tier,
            'percentage'   => (int) round($percentage),
        ];
    }
}
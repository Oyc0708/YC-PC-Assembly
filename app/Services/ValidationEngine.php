<?php

namespace App\Services;

class ValidationEngine
{
    /**
     * Safely extract a property whether the item is an Object (Eloquent Model) or an Array.
     */
    public function getProp($item, string $key, $default = null)
    {
        if (is_array($item)) {
            return $item[$key] ?? $default;
        }
        if (is_object($item)) {
            return $item->$key ?? $default;
        }
        return $default;
    }

    /**
     * Normalize strings to prevent false mismatches (e.g., "AM5 " vs "am5").
     */
    public function normalizeStr($str): string
    {
        if (empty($str)) {
            return '';
        }
        return strtolower(preg_replace('/[\s\-]+/', '', (string) $str));
    }

    /**
     * Smart Form Factor Hierarchy
     * Assigns a numeric size value to safely compare cases and motherboards.
     */
    public function getFormFactorSize(string $ff): int
    {
        $ff = $this->normalizeStr($ff);
        if (strpos($ff, 'eatx') !== false || strpos($ff, 'extended') !== false) return 4;
        if (strpos($ff, 'atx') !== false && strpos($ff, 'micro') === false && strpos($ff, 'mini') === false) return 3;
        if (strpos($ff, 'micro') !== false || strpos($ff, 'matx') !== false) return 2;
        if (strpos($ff, 'mini') !== false || strpos($ff, 'itx') !== false) return 1;
        return 3; // Default fallback assumes standard ATX
    }

    /**
     * Run all hardware constraint checks against the current build session.
     */
    public function runChecks(array $build): array
    {
        $issues = [];
        $warnings = [];
        
        // Base wattage for motherboard, fans, and controller board
        $estWattage = 50; 

        // Safely extract parts
        $cpu     = $build['cpu'] ?? null;
        $mobo    = $build['mobo'] ?? null;
        $ram     = $build['ram'] ?? null;
        $gpu     = $build['gpu'] ?? null;
        $storage = $build['storage'] ?? null;
        $cooler  = $build['cooler'] ?? null;
        $case    = $build['case'] ?? null;
        $psu     = $build['psu'] ?? null;

        // Safely extract properties
        $cpuName   = $this->getProp($cpu, 'name', 'CPU');
        $cpuSocket = $this->normalizeStr($this->getProp($cpu, 'socket'));
        $cpuTdp    = (int) $this->getProp($cpu, 'tdp', 65);
        
        $moboName       = $this->getProp($mobo, 'name', 'Motherboard');
        $moboSocket     = $this->normalizeStr($this->getProp($mobo, 'socket'));
        $moboRamType    = $this->normalizeStr($this->getProp($mobo, 'ram_type'));
        $moboFormFactor = $this->getProp($mobo, 'form_factor');

        $ramName = $this->getProp($ram, 'name', 'RAM');
        $ramType = $this->normalizeStr($this->getProp($ram, 'type'));

        $gpuName   = $this->getProp($gpu, 'name', 'GPU');
        $gpuLength = $this->getProp($gpu, 'length_mm');
        $gpuTdp    = (int) $this->getProp($gpu, 'tdp', 0);

        $storageName = $this->getProp($storage, 'name', 'Storage');

        $coolerName   = $this->getProp($cooler, 'name', 'Cooler');
        $coolerMaxTdp = $this->getProp($cooler, 'max_tdp');

        $caseName         = $this->getProp($case, 'name', 'Case');
        $caseMaxGpuLength = $this->getProp($case, 'max_gpu_length_mm');
        $caseFormFactor   = $this->getProp($case, 'form_factor');

        $psuName    = $this->getProp($psu, 'name', 'PSU');
        $psuWattage = $this->getProp($psu, 'wattage');

        // 1. CPU & Motherboard Socket Compatibility
        if ($cpu && $mobo) {
            if (empty($cpuSocket) || empty($moboSocket) || $cpuSocket === 'unknown' || $moboSocket === 'unknown') {
                $warnings[] = "Missing socket data. Please verify {$cpuName} and {$moboName} compatibility manually.";
            } elseif (strpos($cpuSocket, $moboSocket) === false && strpos($moboSocket, $cpuSocket) === false) {
                $issues[] = "Socket Mismatch: {$cpuName} requires an {$this->getProp($cpu, 'socket')} socket, but the {$moboName} provides {$this->getProp($mobo, 'socket')}.";
            }
        }

        // 2. Motherboard & RAM Generation Compatibility
        if ($mobo && $ram) {
            if (empty($moboRamType) || empty($ramType) || $moboRamType === 'unknown' || $ramType === 'unknown') {
                $warnings[] = "Missing RAM generation data. Please verify {$ramName} works with the {$moboName} manually.";
            } elseif (strpos($ramType, $moboRamType) === false && strpos($moboRamType, $ramType) === false) {
                $issues[] = "Memory Mismatch: The {$moboName} supports {$this->getProp($mobo, 'ram_type')} RAM, but you selected {$this->getProp($ram, 'type')} modules.";
            }
        }

        // 3. Storage Availability Check
        if (!$storage) {
            $warnings[] = "No storage drive selected. You will need a storage drive to install an operating system.";
        } else {
            $estWattage += 10; // Nominal power allocation for storage drives
        }

        // 4. CPU TDP vs Cooler Thermal Capacity
        if ($cpu && $cooler) {
            if ($coolerMaxTdp === null) {
                $warnings[] = "Missing cooling capacity data for the {$coolerName}. Verify it can cool a {$cpuTdp}W CPU.";
            } else {
                $requiredThermalCapacity = $cpuTdp * 1.1;
                if ($coolerMaxTdp < $requiredThermalCapacity) {
                    $warnings[] = "Thermal Limit: The {$coolerName} (Max TDP: {$coolerMaxTdp}W) may struggle to cool the {$cpuName} (Requires ~" . (int)$requiredThermalCapacity . "W) under heavy load.";
                }
            }
        }

        // 5. GPU Length vs Case Physical Clearance
        if ($gpu && $case) {
            if ($gpuLength === null || $caseMaxGpuLength === null) {
                $warnings[] = "Missing physical dimension data. Please manually verify the {$gpuName} fits inside the {$caseName}.";
            } elseif ($gpuLength > $caseMaxGpuLength) {
                $issues[] = "Clearance Error: The {$gpuName} is {$gpuLength}mm long, but the {$caseName} only supports GPUs up to {$caseMaxGpuLength}mm.";
            }
        }

        // 6. Smart Form Factor Clearance
        if ($mobo && $case && $moboFormFactor && $caseFormFactor) {
            $moboSize = $this->getFormFactorSize($moboFormFactor);
            $caseSize = $this->getFormFactorSize($caseFormFactor);
            
            if ($moboSize > $caseSize) {
                $issues[] = "Form Factor Mismatch: The {$moboName} ({$moboFormFactor}) is too large to fit inside the {$caseName} ({$caseFormFactor}).";
            }
        }

        // 7. System Power Draw vs PSU Wattage Output
        if ($cpu) $estWattage += $cpuTdp;
        if ($gpu) $estWattage += $gpuTdp;

        if ($psu) {
            if ($psuWattage === null) {
                $warnings[] = "Missing wattage rating for the {$psuName}. Please manually verify it provides at least " . (int)($estWattage * 1.2) . "W.";
            } else {
                $recommendedWattage = $estWattage * 1.2;
                if ($psuWattage < $estWattage) {
                    $issues[] = "Power Deficit: Your system draws an estimated {$estWattage}W, but the {$psuName} only provides {$psuWattage}W. The system may shut down under load.";
                } elseif ($psuWattage < $recommendedWattage) {
                    $warnings[] = "Power Headroom: Your {$psuName} ({$psuWattage}W) barely meets the requirement of {$estWattage}W. A PSU with at least " . (int)$recommendedWattage . "W is recommended for stability.";
                }
            }
        }

        $powerAnalytics = isset($psu) ? $this->analyzePowerEfficiency($estWattage, $psuWattage) : null;
        $bottleneckAnalytics = ($cpu && $gpu) ? $this->calculateBottleneck($cpu, $gpu) : null;

        return [
            'is_valid'          => count($issues) === 0,
            'warnings'          => $warnings,
            'issues'            => $issues,
            'estimated_wattage' => $estWattage,
            'power_analytics'   => $powerAnalytics,
            'bottleneck'        => $bottleneckAnalytics
        ];
    }

    /**
     * Calculate dynamic performance estimates using Spec-Based Heuristics.
     */
    public function calculateScores(array $build): array
    {
        $checks = $this->runChecks($build);
        if (!$checks['is_valid']) {
            return [
                'gaming'       => 0,
                'productivity' => 0,
                'overall'      => 0,
                'tier'         => 'Incompatible Build',
                'percentage'   => 0,
                'breakdown'    => ['error' => 'Add compatible parts to calculate scores.']
            ];
        }

        // 1. CPU Heuristic Score
        $cpuCores = (int) $this->getProp($build['cpu'] ?? null, 'cores', 2);
        $cpuBoost = (float) $this->getProp($build['cpu'] ?? null, 'boost_clock', 3.0);
        $cpuTdp   = (int) $this->getProp($build['cpu'] ?? null, 'tdp', 65);
        $cpuScore = (int) round(($cpuCores * $cpuBoost * 120) + ($cpuTdp * 5));

        // 2. GPU Heuristic Score
        $gpuVram  = (int) $this->getProp($build['gpu'] ?? null, 'memory', 2);
        $gpuClock = (int) $this->getProp($build['gpu'] ?? null, 'clock_speed', 1000);
        $gpuTdp   = (int) $this->getProp($build['gpu'] ?? null, 'tdp', 75);
        $gpuScore = (int) round(($gpuVram * 200) + ($gpuClock * 1.5) + ($gpuTdp * 10));

        // 3. RAM Heuristic Score
        $ramCap   = (int) $this->getProp($build['ram'] ?? null, 'capacity', 8);
        $ramSpeed = (int) $this->getProp($build['ram'] ?? null, 'speed', 2133);
        $ramScore = (int) round(($ramCap * 80) + ($ramSpeed * 0.8));

        // 4. Storage Heuristic Score
        $storageCap  = (int) $this->getProp($build['storage'] ?? null, 'capacity', 256);
        $storageNvme = $this->getProp($build['storage'] ?? null, 'nvme');
        $isNvme      = ($storageNvme === 'true' || $storageNvme === true || $storageNvme === 1);
        $storageScore= (int) round(($storageCap * 0.4) + ($isNvme ? 800 : 200));

        // Dynamic Bottleneck Detection
        $bottleneckPenalty = 1.0;
        $balanceMsg = 'Optimal balance (No penalty applied).';

        if ($gpuScore > 0 && $cpuScore > 0) {
            if ($gpuScore > $cpuScore * 1.8) {
                $bottleneckPenalty = 0.88; // 12% Penalty
                $balanceMsg = 'CPU Bottleneck (-12% penalty applied). Your CPU limits GPU frame rates.';
            } elseif ($cpuScore > $gpuScore * 1.5) {
                $bottleneckPenalty = 0.95; // 5% Penalty
                $balanceMsg = 'GPU Bottleneck (-5% penalty applied). GPU holds back CPU in gaming.';
            } elseif ($ramScore > 0 && $ramScore < ($cpuScore * 0.5)) {
                $bottleneckPenalty = 0.90; // 10% Penalty
                $balanceMsg = 'RAM Bottleneck (-10% penalty applied). Memory speed/capacity is low.';
            }
        }

        // Scaled score distribution incorporating storage (load times & file output)
        $scalingFactor = 1.5;
        $gamingRaw = ($gpuScore * 0.60) + ($cpuScore * 0.25) + ($ramScore * 0.10) + ($storageScore * 0.05);
        $prodRaw   = ($cpuScore * 0.45) + ($ramScore * 0.25) + ($gpuScore * 0.20) + ($storageScore * 0.10);

        $gaming = (int) round($gamingRaw * $scalingFactor * $bottleneckPenalty);
        $productivity = (int) round($prodRaw * $scalingFactor * $bottleneckPenalty);
        $overall = (int) round(($gaming * 0.55) + ($productivity * 0.45));

        // Tiering logic
        $tier = 'Entry-Level (1080p Low)';
        if ($overall >= 11500) $tier = 'God Tier (4K Ultra)';
        elseif ($overall >= 8500) $tier = 'Enthusiast (1440p Ultra)';
        elseif ($overall >= 6000) $tier = 'High Performance (1440p High)';
        elseif ($overall >= 4000) $tier = 'Mid-Range (1080p High)';

        $percentage = max(0, min(100, ($overall / 13000) * 100));

        // Detailed mathematical breakdown
        $breakdown = [
            'cpu_math'             => "({$cpuCores}C × {$cpuBoost}GHz × 120) + {$cpuTdp}W",
            'gpu_math'             => "({$gpuVram}GB × 200) + {$gpuClock}MHz + {$gpuTdp}W",
            'ram_math'             => "({$ramCap}GB × 80) + {$ramSpeed}MHz",
            'storage_math'         => "({$storageCap}GB × 0.4) + " . ($isNvme ? '800 (NVMe)' : '200 (SATA)'),
            'gpu_contribution'     => "GPU Synthetic: {$gpuScore}",
            'cpu_contribution'     => "CPU Synthetic: {$cpuScore}",
            'ram_contribution'     => "RAM Synthetic: {$ramScore}",
            'storage_contribution' => "Storage Synthetic: {$storageScore}",
            'balance'              => $balanceMsg,
            'multiplier'           => $bottleneckPenalty
        ];

        return [
            'gaming'       => $gaming,
            'productivity' => $productivity,
            'overall'      => $overall,
            'tier'         => $tier,
            'percentage'   => (int) round($percentage),
            'breakdown'    => $breakdown,
        ];
    }

    public function analyzePowerEfficiency($estWattage, $psuWattage)
    {
        if (!$psuWattage || $psuWattage <= 0) return null;

        $loadPercentage = ($estWattage / $psuWattage) * 100;
        
        $status = 'Optimal';
        $color = 'var(--neon-green)';
        $message = 'Your PSU will operate at peak efficiency.';

        if ($loadPercentage < 40) {
            $status = 'Underutilized';
            $color = 'var(--neon-blue)';
            $message = 'PSU is overkill. It will run quietly but slightly less efficiently.';
        } elseif ($loadPercentage > 85) {
            $status = 'Heavy Load';
            $color = 'var(--neon-orange)';
            $message = 'Approaching limits. Transients may cause system instability.';
        } elseif ($loadPercentage >= 100) {
            $status = 'Overloaded';
            $color = 'var(--neon-red)';
            $message = 'Danger: PSU wattage is insufficient for this system!';
        }

        return [
            'load_percentage' => round($loadPercentage),
            'status' => $status,
            'color' => $color,
            'message' => $message
        ];
    }

    /**
     * Heuristic Bottleneck Calculator
     */
    public function calculateBottleneck($cpu, $gpu)
    {
        if (!$cpu || !$gpu) return null;

        $cpuPower = ((int)$this->getProp($cpu, 'cores', 2) * (float)$this->getProp($cpu, 'boost_clock', 3.0)) * 10; 
        $gpuPower = ((int)$this->getProp($gpu, 'memory', 2) * ((int)$this->getProp($gpu, 'clock_speed', 1000) / 100));

        if ($gpuPower <= 0) return null;

        $ratio = $cpuPower / $gpuPower;

        $status = 'Balanced';
        $color = 'var(--neon-green)';
        $warning = 'CPU and GPU are a great match.';

        if ($ratio < 0.6) {
            $status = 'CPU Bottleneck';
            $color = 'var(--neon-red)';
            $warning = 'Your CPU is too weak for this GPU. Expect stuttering in CPU-heavy games.';
        } elseif ($ratio > 3.5) {
            $status = 'GPU Bottleneck';
            $color = 'var(--neon-orange)';
            $warning = 'Your GPU is too weak for this CPU. Graphics will hold back your framerate.';
        }

        return [
            'status' => $status,
            'color' => $color,
            'message' => $warning,
            'ratio' => round($ratio, 2)
        ];
    }
}
<?php

namespace App\Services;

class ValidationEngine
{
    public function getProp($item, string $key, $default = null)
    {
        if (is_array($item)) return $item[$key] ?? $default;
        if (is_object($item)) return $item->$key ?? $default;
        return $default;
    }

    public function normalizeStr($str): string
    {
        if (empty($str)) return '';
        return strtolower(preg_replace('/[\s\-]+/', '', (string) $str));
    }

    public function getRamGen(string $type): string
    {
        $type = $this->normalizeStr($type);
        if (preg_match('/ddr\d/', $type, $matches)) {
            return $matches[0];
        }
        return $type;
    }

    public function cleanSocket(string $socket): string
    {
        $s = $this->normalizeStr($socket);
        return str_replace(['socket', 'lga'], '', $s);
    }

    public function getFormFactorSize(string $ff): int
    {
        $ff = $this->normalizeStr($ff);
        if (strpos($ff, 'eatx') !== false || strpos($ff, 'extended') !== false) return 4;
        if (strpos($ff, 'atx') !== false && strpos($ff, 'micro') === false && strpos($ff, 'mini') === false) return 3;
        if (strpos($ff, 'micro') !== false || strpos($ff, 'matx') !== false) return 2;
        if (strpos($ff, 'mini') !== false || strpos($ff, 'itx') !== false) return 1;
        return 3;
    }

    /**
     * Calculates Peak CPU Power consumption (PL2 / Max Turbo Power)
     */
    public function getCpuPeakPower($cpu): float
    {
        if (!$cpu) return 0;
        
        $maxTurbo = $this->getProp($cpu, 'max_turbo_power');
        if ($maxTurbo && (float)$maxTurbo > 0) return (float) $maxTurbo;

        $maxTdp = $this->getProp($cpu, 'max_tdp');
        if ($maxTdp && (float)$maxTdp > 0) return (float) $maxTdp;

        $baseTdp = (float) $this->getProp($cpu, 'tdp', 65);
        return $baseTdp * 1.5; // PL2 transient default multiplier
    }

    /**
     * Calculates Peak GPU Power draw including 40% transient voltage spikes
     */
    public function getGpuPeakPower($gpu): float
    {
        if (!$gpu) return 0;
        $baseTdp = (float) $this->getProp($gpu, 'tdp', 150);
        return $baseTdp * 1.4; // 40% transient spike allowance
    }

    public function runChecks(array $build): array
    {
        $issues = [];
        $warnings = [];

        $cpu     = $build['cpu'] ?? null;
        $mobo    = $build['mobo'] ?? null;
        $ram     = $build['ram'] ?? null;
        $gpu     = $build['gpu'] ?? null;
        $storage = $build['storage'] ?? null;
        $cooler  = $build['cooler'] ?? null;
        $case    = $build['case'] ?? null;
        $psu     = $build['psu'] ?? null;

        $cpuName   = $this->getProp($cpu, 'name', 'CPU');
        $cpuSocket = $this->getProp($cpu, 'socket');
        
        $moboName       = $this->getProp($mobo, 'name', 'Motherboard');
        $moboSocket     = $this->getProp($mobo, 'socket');
        $moboRamType    = $this->getProp($mobo, 'ram_type');
        $moboFormFactor = $this->getProp($mobo, 'form_factor');

        $ramName = $this->getProp($ram, 'name', 'RAM');
        $ramType = $this->getProp($ram, 'type');

        $gpuName   = $this->getProp($gpu, 'name', 'GPU');
        $gpuLength = $this->getProp($gpu, 'length_mm');

        $coolerName   = $this->getProp($cooler, 'name', 'Cooler');
        $coolerMaxTdp = $this->getProp($cooler, 'max_tdp');

        $caseName         = $this->getProp($case, 'name', 'Case');
        $caseMaxGpuLength = $this->getProp($case, 'max_gpu_length_mm');
        $caseFormFactor   = $this->getProp($case, 'form_factor');

        $psuName    = $this->getProp($psu, 'name', 'PSU');
        $psuWattage = $this->getProp($psu, 'wattage');

        // 1. Socket Compatibility
        if ($cpu && $mobo) {
            if (empty($cpuSocket) || empty($moboSocket) || strtolower($cpuSocket) === 'unknown' || strtolower($moboSocket) === 'unknown') {
                $warnings[] = "Missing socket data. Please verify {$cpuName} and {$moboName} compatibility manually.";
            } else {
                $cleanCpu = $this->cleanSocket($cpuSocket);
                $cleanMobo = $this->cleanSocket($moboSocket);
                if ($cleanCpu !== $cleanMobo) {
                    $issues[] = "Socket Mismatch: {$cpuName} requires an {$cpuSocket} socket, but the {$moboName} provides {$moboSocket}.";
                }
            }
        }

        // 2. RAM Generation Compatibility
        if ($mobo && $ram) {
            if (empty($moboRamType) || empty($ramType) || strtolower($moboRamType) === 'unknown' || strtolower($ramType) === 'unknown') {
                $warnings[] = "Missing RAM generation data. Please verify {$ramName} works with the {$moboName} manually.";
            } else {
                $cleanMoboRam = $this->getRamGen($moboRamType);
                $cleanRamType = $this->getRamGen($ramType);
                if ($cleanMoboRam !== $cleanRamType) {
                    $issues[] = "Memory Mismatch: The {$moboName} supports {$moboRamType} RAM, but you selected {$ramType} modules.";
                }
            }
        }

        // 3. Storage Drive Check
        if (!$storage) {
            $warnings[] = "No storage drive selected. You will need a storage drive to install an operating system.";
        }

        // 4. Thermal Dissipation Check (Evaluated against CPU Peak PL2 Power)
        if ($cpu && $cooler) {
            $cpuPeakPower = $this->getCpuPeakPower($cpu);
            if ($coolerMaxTdp === null || (float)$coolerMaxTdp <= 0) {
                $warnings[] = "Missing cooling capacity data for the {$coolerName}. Verify it can dissipate {$cpuPeakPower}W peak CPU heat.";
            } else {
                if ((float)$coolerMaxTdp < $cpuPeakPower) {
                    $issues[] = "Insufficient Cooling: The {$coolerName} (Rated: {$coolerMaxTdp}W) cannot handle {$cpuName}'s Max Turbo Power ({$cpuPeakPower}W). Severe thermal throttling will occur.";
                } elseif ((float)$coolerMaxTdp < ($cpuPeakPower * 1.15)) {
                    $warnings[] = "Tight Thermal Margin: The {$coolerName} capacity ({$coolerMaxTdp}W) is near {$cpuName}'s Max Turbo limit ({$cpuPeakPower}W). Expect high fan noise under load.";
                }
            }
        }

        // 5. GPU Clearance Check
        if ($gpu && $case) {
            $gpuLen = (float) $gpuLength;
            $caseMaxLen = (float) $caseMaxGpuLength;

            if (empty($gpuLength) || empty($caseMaxGpuLength) || $gpuLen <= 0 || $caseMaxLen <= 0) {
                $warnings[] = "Missing physical dimension data for {$gpuName} or {$caseName}. Please manually verify component clearance.";
            } elseif ($gpuLen > $caseMaxLen) {
                $issues[] = "Clearance Error: The {$gpuName} is {$gpuLen}mm long, but the {$caseName} only supports GPUs up to {$caseMaxLen}mm.";
            }
        }

        // 6. Form Factor Check
        if ($mobo && $case && $moboFormFactor && $caseFormFactor) {
            $moboSize = $this->getFormFactorSize($moboFormFactor);
            $caseSize = $this->getFormFactorSize($caseFormFactor);
            
            if ($moboSize > $caseSize) {
                $issues[] = "Form Factor Mismatch: The {$moboName} ({$moboFormFactor}) is too large to fit inside the {$caseName} ({$caseFormFactor}).";
            }
        }

        // 7. System Power Draw Calculation (CPU Peak PL2 + GPU Transient Peak + System Base)
        $cpuPeak = $this->getCpuPeakPower($cpu);
        $gpuPeak = $this->getGpuPeakPower($gpu);
        $systemBase = $storage ? 65 : 55;

        $estPeakWattage = (int) round($cpuPeak + $gpuPeak + $systemBase);

        if ($psu) {
            if ($psuWattage === null || (float)$psuWattage <= 0) {
                $warnings[] = "Missing wattage rating for the {$psuName}. Verify it provides at least " . (int)($estPeakWattage * 1.2) . "W.";
            } else {
                $recommendedWattage = (int) (ceil(($estPeakWattage * 1.20) / 50) * 50);

                if ((float)$psuWattage < $estPeakWattage) {
                    $issues[] = "Power Deficit: Peak system draw with GPU transient spikes requires {$estPeakWattage}W, but the {$psuName} only supplies {$psuWattage}W. Emergency shutdowns may occur under heavy gaming load.";
                } elseif ((float)$psuWattage < $recommendedWattage) {
                    $warnings[] = "Low Power Headroom: Your {$psuName} ({$psuWattage}W) covers peak power ({$estPeakWattage}W), but a PSU rated for at least {$recommendedWattage}W is recommended for efficiency and peak transient safety.";
                }
            }
        }

        $powerAnalytics = isset($psu) ? $this->analyzePowerEfficiency($estPeakWattage, $psuWattage) : null;
        $bottleneckAnalytics = ($cpu && $gpu) ? $this->calculateBottleneck($cpu, $gpu) : null;

        return [
            'is_valid'          => count($issues) === 0,
            'warnings'          => $warnings,
            'issues'            => $issues,
            'estimated_wattage' => $estPeakWattage,
            'power_analytics'   => $powerAnalytics,
            'bottleneck'        => $bottleneckAnalytics
        ];
    }

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

        $cpuCores = (int) $this->getProp($build['cpu'] ?? null, 'cores', 2);
        $cpuBoost = (float) $this->getProp($build['cpu'] ?? null, 'boost_clock', 3.0);
        $cpuScore = (int) round($cpuCores * $cpuBoost * 145);

        $gpuVram  = (int) $this->getProp($build['gpu'] ?? null, 'memory', 2);
        $gpuClock = (float) $this->getProp($build['gpu'] ?? null, 'boost_clock', 1500);
        $gpuScore = (int) round(($gpuVram * 350) + ($gpuClock * 0.25));

        $ramCap   = (int) $this->getProp($build['ram'] ?? null, 'capacity', 8);
        $ramSpeed = (int) $this->getProp($build['ram'] ?? null, 'speed', 2133);
        $ramScore = (int) round(($ramCap * 80) + ($ramSpeed * 0.8));

        $storageCap  = (int) $this->getProp($build['storage'] ?? null, 'capacity', 256);
        $storageNvme = $this->getProp($build['storage'] ?? null, 'nvme');
        $isNvme      = ($storageNvme === 'true' || $storageNvme === true || $storageNvme === 1);
        $storageScore= (int) round(($storageCap * 0.4) + ($isNvme ? 800 : 200));

        $bottleneckPenalty = 1.0;
        $balanceMsg = 'Optimal balance (No penalty applied).';

        if ($gpuScore > 0 && $cpuScore > 0) {
            if ($gpuScore > $cpuScore * 1.8) {
                $bottleneckPenalty = 0.88; 
                $balanceMsg = 'CPU Bottleneck (-12% penalty applied). Your CPU limits GPU frame rates.';
            } elseif ($cpuScore > $gpuScore * 1.5) {
                $bottleneckPenalty = 1.0; 
                $balanceMsg = 'GPU Bound (GPU running at max capacity; CPU has ample headroom. No penalty applied).';
            } elseif ($ramScore > 0 && $ramScore < ($cpuScore * 0.5)) {
                $bottleneckPenalty = 0.90; 
                $balanceMsg = 'RAM Bottleneck (-10% penalty applied). Memory speed/capacity is low.';
            }
        }

        $scalingFactor = 1.5;
        $gamingRaw = ($gpuScore * 0.60) + ($cpuScore * 0.25) + ($ramScore * 0.10) + ($storageScore * 0.05);
        $prodRaw   = ($cpuScore * 0.45) + ($ramScore * 0.25) + ($gpuScore * 0.20) + ($storageScore * 0.10);

        $gaming = (int) round($gamingRaw * $scalingFactor * $bottleneckPenalty);
        $productivity = (int) round($prodRaw * $scalingFactor * $bottleneckPenalty);
        $overall = (int) round(($gaming * 0.55) + ($productivity * 0.45));

        $tier = 'Entry-Level (1080p Low)';
        if ($overall >= 11500) $tier = 'God Tier (4K Ultra)';
        elseif ($overall >= 8500) $tier = 'Enthusiast (1440p Ultra)';
        elseif ($overall >= 6000) $tier = 'High Performance (1440p High)';
        elseif ($overall >= 4000) $tier = 'Mid-Range (1080p High)';

        $percentage = max(0, min(100, ($overall / 13000) * 100));

        return [
            'gaming'       => $gaming,
            'productivity' => $productivity,
            'overall'      => $overall,
            'tier'         => $tier,
            'percentage'   => (int) round($percentage),
            'breakdown'    => [
                'cpu_math'             => "({$cpuCores} Cores × {$cpuBoost} GHz × 145)",
                'gpu_math'             => "({$gpuVram} GB VRAM × 350) + ({$gpuClock} MHz × 0.25)",
                'ram_math'             => "({$ramCap} GB × 80) + ({$ramSpeed} MHz × 0.8)",
                'storage_math'         => "({$storageCap} GB × 0.4) + " . ($isNvme ? '800 (NVMe)' : '200 (SATA)'),
                'gpu_contribution'     => "GPU Synthetic: {$gpuScore}",
                'cpu_contribution'     => "CPU Synthetic: {$cpuScore}",
                'ram_contribution'     => "RAM Synthetic: {$ramScore}",
                'storage_contribution' => "Storage Synthetic: {$storageScore}",
                'balance'              => $balanceMsg,
                'multiplier'           => $bottleneckPenalty
            ],
        ];
    }

    public function analyzePowerEfficiency($estWattage, $psuWattage)
    {
        if (!$psuWattage || $psuWattage <= 0) return null;

        $loadPercentage = ($estWattage / $psuWattage) * 100;
        
        $status = 'Optimal';
        $color = 'var(--neon-green)';
        $message = 'Your PSU handles peak turbo and transient GPU spikes with efficiency.';

        if ($loadPercentage < 40) {
            $status = 'Underutilized';
            $color = 'var(--neon-blue)';
            $message = 'PSU wattage is higher than needed. Runs silently but slightly less efficient.';
        } elseif ($loadPercentage > 85) {
            $status = 'Heavy Load';
            $color = 'var(--neon-orange)';
            $message = 'Operating near capacity limits under maximum system load.';
        } elseif ($loadPercentage >= 100) {
            $status = 'Overloaded';
            $color = 'var(--neon-red)';
            $message = 'Danger: PSU rated wattage cannot sustain peak transient draw!';
        }

        return [
            'load_percentage' => round($loadPercentage),
            'status' => $status,
            'color' => $color,
            'message' => $message
        ];
    }

    public function calculateBottleneck($cpu, $gpu)
    {
        if (!$cpu || !$gpu) return null;

        $cpuPower = ((int)$this->getProp($cpu, 'cores', 2) * (float)$this->getProp($cpu, 'boost_clock', 3.0)) * 10; 
        $gpuVram  = (int) $this->getProp($gpu, 'memory', 2);
        $gpuClock = (float) $this->getProp($gpu, 'boost_clock', 1500);
        $gpuPower = ($gpuVram * 20) + ($gpuClock / 40);
        
        if ($gpuPower <= 0) return null;

        $ratio = $cpuPower / $gpuPower;

        $status = 'Balanced';
        $color = 'var(--neon-green)';
        $warning = 'CPU and GPU are well matched.';

        if ($ratio < 0.65) {
            $status = 'CPU Bottleneck';
            $color = 'var(--neon-red)';
            $warning = 'Your CPU is underpowered relative to the GPU, limiting maximum frame rates.';
        } elseif ($ratio > 3.2) {
            $status = 'GPU Bound';
            $color = 'var(--neon-green)';
            $warning = 'Your GPU is operating at maximum output while your CPU provides extra headroom.';
        }

        return [
            'status'  => $status,
            'color'   => $color,
            'message' => $warning,
            'ratio'   => round($ratio, 2)
        ];
    }
}
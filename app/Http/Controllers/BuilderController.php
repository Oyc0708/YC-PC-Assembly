<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ValidationEngine;
use App\Models\{Cpu, Cooler, Motherboard, Ram, Gpu, Psu, PcCase};

class BuilderController extends Controller
{
    protected ValidationEngine $validationEngine;

    // Map URL categories to their respective Eloquent Models
    protected array $modelMap = [
        'cpu'    => Cpu::class,
        'cooler' => Cooler::class,
        'mobo'   => Motherboard::class,
        'ram'    => Ram::class,
        'gpu'    => Gpu::class,
        'psu'    => Psu::class,
        'case'   => PcCase::class,
    ];

    public function __construct(ValidationEngine $validationEngine)
    {
        $this->validationEngine = $validationEngine;
    }

    // 1. Display the Build Summary
    public function index()
    {
        $currentBuild = session('current_build', []);
        $totalCost = collect($currentBuild)->filter()->sum('price');

        $compatibility = null;
        $scores = null; // Initialize variable

        if (!empty($currentBuild)) {
            $compatibility = $this->validationEngine->runChecks($currentBuild);
            $scores = $this->validationEngine->calculateScores($currentBuild); // Calculate
        }

        // Add 'scores' to compact()
        return view('builder.index', compact('currentBuild', 'totalCost', 'compatibility', 'scores'));
    }

    // 2. Display the Catalog for a Specific Component
    public function selectCategory($category)
    {
        if (!array_key_exists($category, $this->modelMap)) {
            abort(404, 'Category not found');
        }

        $model = $this->modelMap[$category];
        
        // Paginate results so the page loads instantly, even with 3000 GPUs
        $parts = $model::orderBy('name')->paginate(20);

        return view('builder.select', compact('parts', 'category'));
    }

    // 3. Add a Part to the Session
    public function addPart(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'id'       => 'required|string',
        ]);

        $category = $request->category;
        $model = $this->modelMap[$category];
        $part = $model::findOrFail($request->id);

        $build = session('current_build', []);
        $build[$category] = $part;
        session(['current_build' => $build]);

        return redirect()->route('builder.index')->with('success', "{$part->name} added to your build!");
    }

    // 4. Remove a Part from the Session
    public function removePart(Request $request)
    {
        $category = $request->input('category');
        $build = session('current_build', []);
        
        if (isset($build[$category])) {
            unset($build[$category]);
            session(['current_build' => $build]);
        }

        return redirect()->route('builder.index');
    }

    public function generateAutoBuild(Request $request)
    {
        $request->validate([
            'budget' => 'required|numeric|min:2000|max:20000',
            'use_case' => 'required|string',
            'resolution' => 'required|string',
        ]);

        $budget = (float) $request->budget;
        
        // Define baseline constraints (PHP 7.4 compatible)
        $minGpuScore = 0; // 1080p default
        if ($request->resolution === '2k') {
            $minGpuScore = 80;
        } elseif ($request->resolution === '4k') {
            $minGpuScore = 95;
        }

        // Budget allocation percentages based on workload (PHP 7.4 compatible)
        if ($request->use_case === 'video-editing') {
            $alloc = ['gpu' => 0.35, 'cpu' => 0.25, 'mobo' => 0.12, 'ram' => 0.13, 'cooler' => 0.05, 'psu' => 0.05, 'case' => 0.05];
        } elseif ($request->use_case === 'office') {
            $alloc = ['gpu' => 0.15, 'cpu' => 0.35, 'mobo' => 0.15, 'ram' => 0.15, 'cooler' => 0.08, 'psu' => 0.06, 'case' => 0.06];
        } else {
            // Default: Gaming
            $alloc = ['gpu' => 0.45, 'cpu' => 0.20, 'mobo' => 0.10, 'ram' => 0.08, 'cooler' => 0.05, 'psu' => 0.07, 'case' => 0.05];
        }

        try {
            // 1. Core Platform: GPU & CPU
            $gpu = Gpu::where('score', '>=', $minGpuScore)
                      ->where('price', '<=', $budget * ($alloc['gpu'] + 0.10)) // Allow 10% flex
                      ->orderByDesc('score')->first();

            if (!$gpu) throw new \Exception('Budget is too low for the requested resolution target.');

            $cpu = Cpu::where('price', '<=', $budget * ($alloc['cpu'] + 0.05))
                      ->orderByDesc('score')->first();

            if (!$cpu) throw new \Exception('Could not find a CPU in this budget range.');

            // 2. Motherboard (Must match CPU Socket)
            $mobo = Motherboard::where('socket', $cpu->socket)
                               ->where('price', '<=', $budget * ($alloc['mobo'] + 0.05))
                               ->orderByDesc('price')->first();
            if (!$mobo) $mobo = Motherboard::where('socket', $cpu->socket)->first(); // Fallback

            if (!$mobo) throw new \Exception("No motherboards found for socket {$cpu->socket}.");

            // 3. RAM (Must match Motherboard Generation)
            $ram = Ram::where('type', $mobo->ram_type)
                      ->where('price', '<=', $budget * ($alloc['ram'] + 0.05))
                      ->orderByDesc('score')->first();
            if (!$ram) $ram = Ram::where('type', $mobo->ram_type)->first();

            // 4. Cooler (Must handle CPU TDP + 10% headroom)
            $reqTdp = $cpu->tdp * 1.1;
            $cooler = Cooler::where('max_tdp', '>=', $reqTdp)
                            ->where('price', '<=', $budget * ($alloc['cooler'] + 0.05))
                            ->orderByDesc('price')->first();
            if (!$cooler) $cooler = Cooler::where('max_tdp', '>=', $reqTdp)->first();

            // 5. Power Supply (Must handle system wattage + 25% headroom)
            $estPower = $cpu->tdp + $gpu->tdp + 50;
            $psu = Psu::where('wattage', '>=', $estPower * 1.25)
                      ->where('price', '<=', $budget * ($alloc['psu'] + 0.05))
                      ->orderByDesc('price')->first();
            if (!$psu) $psu = Psu::where('wattage', '>=', $estPower * 1.25)->first();

            // 6. Case (Must fit the GPU length)
            $case = PcCase::where('max_gpu_length_mm', '>=', $gpu->length_mm)
                          ->where('price', '<=', $budget * ($alloc['case'] + 0.05))
                          ->orderByDesc('price')->first();
            if (!$case) $case = PcCase::where('max_gpu_length_mm', '>=', $gpu->length_mm)->first();

            // Verify all parts were found
            if (!$ram || !$cooler || !$psu || !$case) {
                throw new \Exception('Failed to find compatible parts for the remaining components.');
            }

            // Save to Session
            $build = compact('cpu', 'cooler', 'mobo', 'ram', 'gpu', 'psu', 'case');
            session(['current_build' => $build]);

            return redirect()->route('builder.index')->with('success', 'Auto-Build generated successfully! Here is your optimized configuration.');

        } catch (\Exception $e) {
            return redirect()->route('builder.index')->with('error', $e->getMessage());
        }
    }
}
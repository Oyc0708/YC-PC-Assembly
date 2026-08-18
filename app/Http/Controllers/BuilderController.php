<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ValidationEngine;
use App\Models\{Cpu, Cooler, Motherboard, Ram, Gpu, Psu, PcCase, Build};

class BuilderController extends Controller
{
    protected ValidationEngine $validationEngine;

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
        $scores = null;

        if (!empty($currentBuild)) {
            $compatibility = $this->validationEngine->runChecks($currentBuild);
            $scores = $this->validationEngine->calculateScores($currentBuild);
        }

        // Fetch saved builds for the authenticated user
        $savedBuilds = collect();
        if (auth()->check()) {
            $savedBuilds = Build::where('user_id', auth()->id())->latest()->get();
        }

        return view('builder.index', compact('currentBuild', 'totalCost', 'compatibility', 'scores', 'savedBuilds'));
    }

    // 2. Display the Catalog with Dynamic Filtering
    public function selectCategory($category, Request $request)
    {
        if (!array_key_exists($category, $this->modelMap)) {
            abort(404, 'Category not found');
        }

        $model = $this->modelMap[$category];
        $query = $model::query();

        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('manufacturer', 'like', $searchTerm);
            });
        }

        if ($request->filled('min_price')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) >= ?', [$request->min_price]);
        }
        if ($request->filled('max_price')) {
            $query->whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$request->max_price]);
        }

        if (in_array($category, ['cpu', 'mobo']) && $request->filled('socket')) {
            $query->where('socket', $request->socket);
        }

        if (in_array($category, ['ram', 'mobo']) && $request->filled('ram_type')) {
            $column = $category === 'ram' ? 'type' : 'ram_type';
            $query->where($column, $request->ram_type);
        }

        $parts = $query->orderBy('name')->paginate(24)->withQueryString();

        $sockets = [];
        $ramTypes = [];
        
        if (in_array($category, ['cpu', 'mobo'])) {
            $sockets = $model::select('socket')->distinct()->whereNotNull('socket')->orderBy('socket')->pluck('socket');
        }
        
        if (in_array($category, ['ram', 'mobo'])) {
            $column = $category === 'ram' ? 'type' : 'ram_type';
            $ramTypes = $model::select($column)->distinct()->whereNotNull($column)->orderBy($column)->pluck($column);
        }

        return view('builder.select', compact('parts', 'category', 'sockets', 'ramTypes'));
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

    // 5. Smart Spec-Driven Auto-Build Engine (Safe Collection Sorting)
    public function generateAutoBuild(Request $request)
    {
        $request->validate([
            'min_budget' => 'required|numeric|min:1000',
            'max_budget' => 'required|numeric|gt:min_budget|max:35000',
            'use_case'   => 'required|string',
            'resolution' => 'required|string',
        ]);

        $maxBudget = (float) $request->max_budget;
        $workload = $request->use_case;

        // Smart Budget Allocations based on use-case
        if ($workload === 'video-editing') {
            $alloc = ['cpu' => 0.35, 'mobo' => 0.10, 'ram' => 0.15, 'gpu' => 0.25, 'case' => 0.05, 'psu' => 0.05, 'cooler' => 0.05];
        } elseif ($workload === 'office') {
            $alloc = ['cpu' => 0.40, 'mobo' => 0.15, 'ram' => 0.15, 'gpu' => 0.10, 'case' => 0.08, 'psu' => 0.07, 'cooler' => 0.05];
        } else { // Gaming
            $alloc = ['cpu' => 0.20, 'mobo' => 0.10, 'ram' => 0.10, 'gpu' => 0.45, 'case' => 0.05, 'psu' => 0.05, 'cooler' => 0.05];
        }

        try {
            $build = [];
            $totalCost = 0;
            $estWattage = 50; // Base wattage for board and drives

            // ==============================================================
            // STEP 1: CPU (Dictates Socket & Base TDP)
            // ==============================================================
            $cpuBudget = $maxBudget * $alloc['cpu'] * 1.2;
            $cpu = Cpu::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$cpuBudget])
                      ->get()
                      ->sortByDesc(function ($c) {
                          // Safely defaults to 0 if columns don't exist
                          $cores = (int) ($c->cores ?? 2);
                          $clock = (float) ($c->boost_clock ?? $c->base_clock ?? 3.0);
                          return ($cores * 100) + $clock;
                      })->first();
                      
            if (!$cpu) $cpu = Cpu::orderByRaw('CAST(price AS DECIMAL(10,2)) ASC')->first();
            if (!$cpu) throw new \Exception('No CPUs found in the database.');
            
            $build['cpu'] = $cpu;
            $totalCost += $cpu->price;
            $estWattage += (int)($cpu->tdp ?? 65);
            $socket = $this->validationEngine->normalizeStr($cpu->socket ?? '');

            // ==============================================================
            // STEP 2: MOTHERBOARD (Must match Socket)
            // ==============================================================
            $moboBudget = $maxBudget * $alloc['mobo'] * 1.3;
            $mobo = Motherboard::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$moboBudget])
                        ->orderByRaw('CAST(price AS DECIMAL(10,2)) DESC')
                        ->get()
                        ->filter(function ($m) use ($socket) {
                            $mSock = $this->validationEngine->normalizeStr($m->socket ?? '');
                            return strpos($socket, $mSock) !== false || strpos($mSock, $socket) !== false;
                        })->first();
                        
            if (!$mobo) { 
                $mobo = Motherboard::orderByRaw('CAST(price AS DECIMAL(10,2)) ASC')->get()->filter(function ($m) use ($socket) {
                    $mSock = $this->validationEngine->normalizeStr($m->socket ?? '');
                    return strpos($socket, $mSock) !== false || strpos($mSock, $socket) !== false;
                })->first();
            }
            if (!$mobo) throw new \Exception("No compatible motherboard found for socket {$cpu->socket}.");
            
            $build['mobo'] = $mobo;
            $totalCost += $mobo->price;
            $ramType = $this->validationEngine->normalizeStr($mobo->ram_type ?? '');
            $moboFormSize = $this->validationEngine->getFormFactorSize($mobo->form_factor ?? '');

            // ==============================================================
            // STEP 3: RAM (Must match Generation)
            // ==============================================================
            $ramBudget = $maxBudget * $alloc['ram'] * 1.3;
            $ram = Ram::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$ramBudget])
                    ->get()
                    ->filter(function ($r) use ($ramType) {
                        $rType = $this->validationEngine->normalizeStr($r->type ?? '');
                        return strpos($ramType, $rType) !== false || strpos($rType, $ramType) !== false;
                    })
                    ->sortByDesc(function ($r) {
                        $cap = (int) ($r->capacity ?? 8);
                        $speed = (int) ($r->speed ?? 2133);
                        return ($cap * 1000) + $speed;
                    })->first();

            if (!$ram) { 
                $ram = Ram::orderByRaw('CAST(price AS DECIMAL(10,2)) ASC')->get()->filter(function ($r) use ($ramType) {
                    $rType = $this->validationEngine->normalizeStr($r->type ?? '');
                    return strpos($ramType, $rType) !== false || strpos($rType, $ramType) !== false;
                })->first();
            }
            if (!$ram) throw new \Exception("No compatible {$mobo->ram_type} RAM found.");
            
            $build['ram'] = $ram;
            $totalCost += $ram->price;

            // ==============================================================
            // STEP 4: GPU (Dictates Physical Clearance)
            // ==============================================================
            $gpuBudget = $maxBudget * $alloc['gpu'] * 1.3;
            $gpu = Gpu::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$gpuBudget])
                      ->get()
                      ->sortByDesc(function ($g) {
                          $vram = (int) ($g->memory ?? 2);
                          $clock = (int) ($g->clock_speed ?? 1000);
                          return ($vram * 1000) + $clock;
                      })->first();
                      
            if (!$gpu) $gpu = Gpu::orderByRaw('CAST(price AS DECIMAL(10,2)) ASC')->first();
            
            if ($gpu) {
                $build['gpu'] = $gpu;
                $totalCost += $gpu->price;
                $estWattage += (int)($gpu->tdp ?? 75);
                $gpuLength = (int)($gpu->length_mm ?? 0);
            } else {
                $gpuLength = 0; 
            }

            // ==============================================================
            // STEP 5: CASE (Must fit Motherboard & GPU physical dimensions)
            // ==============================================================
            $caseBudget = $maxBudget * $alloc['case'] * 1.5;
            $case = PcCase::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$caseBudget])
                        ->orderByRaw('CAST(price AS DECIMAL(10,2)) DESC')
                        ->get()
                        ->filter(function ($c) use ($moboFormSize, $gpuLength) {
                            $cLength = (int) ($c->max_gpu_length_mm ?? 9999);
                            return $cLength >= $gpuLength && $this->validationEngine->getFormFactorSize($c->form_factor ?? '') >= $moboFormSize;
                        })->first();

            if (!$case) { 
                $case = PcCase::orderByRaw('CAST(price AS DECIMAL(10,2)) ASC')->get()->filter(function ($c) use ($moboFormSize, $gpuLength) {
                            $cLength = (int) ($c->max_gpu_length_mm ?? 9999);
                            return $cLength >= $gpuLength && $this->validationEngine->getFormFactorSize($c->form_factor ?? '') >= $moboFormSize;
                        })->first();
            }
            if (!$case) throw new \Exception("No case fits a {$gpuLength}mm GPU and a {$mobo->form_factor} board.");
            
            $build['case'] = $case;
            $totalCost += $case->price;

            // ==============================================================
            // STEP 6: PSU (Must supply System Wattage)
            // ==============================================================
            $psuBudget = $maxBudget * $alloc['psu'] * 1.5;
            $reqWattage = $estWattage * 1.25; // 25% safety overhead
            $psu = Psu::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$psuBudget])
                      ->get()
                      ->filter(function ($p) use ($reqWattage) {
                          return (int)($p->wattage ?? 0) >= $reqWattage;
                      })
                      ->sortByDesc(function ($p) {
                          return (int)($p->wattage ?? 0);
                      })->first();
                      
            if (!$psu) $psu = Psu::get()->filter(function($p) use ($reqWattage) { return (int)($p->wattage ?? 0) >= $reqWattage; })->sortBy('price')->first();
            if (!$psu) throw new \Exception("No PSU found supplying the required {$reqWattage}W.");
            
            $build['psu'] = $psu;
            $totalCost += $psu->price;

            // ==============================================================
            // STEP 7: COOLER (Must handle CPU Thermals)
            // ==============================================================
            $coolerBudget = $maxBudget * $alloc['cooler'] * 1.5;
            $reqThermal = (int)($cpu->tdp ?? 65) * 1.1; // 10% thermal headroom
            $cooler = Cooler::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$coolerBudget])
                            ->get()
                            ->filter(function ($c) use ($reqThermal) {
                                return (int)($c->max_tdp ?? 0) >= $reqThermal;
                            })
                            ->sortByDesc(function ($c) {
                                return (int)($c->max_tdp ?? 0);
                            })->first();
                            
            if (!$cooler) $cooler = Cooler::get()->filter(function($c) use ($reqThermal) { return (int)($c->max_tdp ?? 0) >= $reqThermal; })->sortBy('price')->first();
            if (!$cooler) throw new \Exception("No cooler found capable of handling {$reqThermal}W.");
            
            $build['cooler'] = $cooler;
            $totalCost += $cooler->price;

            // Save to session and redirect
            session(['current_build' => $build]);

            return redirect()->route('builder.index')->with('success', "Optimized Auto-Build Generated! Logically selected high-end components based on raw specs. Total Cost: RM " . number_format($totalCost, 2));

        } catch (\Exception $e) {
            return redirect()->route('builder.index')->with('error', $e->getMessage());
        }
    }

    // 6. Save Build to Database
    public function saveBuild(Request $request)
    {
        $build = session('current_build', []);

        if (empty($build)) {
            return redirect()->route('builder.index')->with('error', 'Your build is empty. Add some components before saving!');
        }

        $totalCost = collect($build)->filter()->sum('price');
        $scores = $this->validationEngine->calculateScores($build);

        Build::create([
            'user_id'       => auth()->id(),
            'name'          => 'Custom Build - ' . date('M j, Y'),
            'cpu_id'        => isset($build['cpu']) ? $build['cpu']->id : null,
            'cooler_id'     => isset($build['cooler']) ? $build['cooler']->id : null,
            'mobo_id'       => isset($build['mobo']) ? $build['mobo']->id : null,
            'ram_id'        => isset($build['ram']) ? $build['ram']->id : null,
            'gpu_id'        => isset($build['gpu']) ? $build['gpu']->id : null,
            'psu_id'        => isset($build['psu']) ? $build['psu']->id : null,
            'case_id'       => isset($build['case']) ? $build['case']->id : null,
            'total_cost'    => $totalCost,
            'overall_score' => $scores['overall'] ?? 0,
        ]);

        return redirect()->route('builder.index')->with('success', 'Configuration saved to your workspace successfully!');
    }

    // 7. Load Build from Database to Session
    public function loadBuild($id)
    {
        $buildRecord = Build::where('user_id', auth()->id())->findOrFail($id);
        
        $build = [];
        if ($buildRecord->cpu_id) $build['cpu'] = Cpu::find($buildRecord->cpu_id);
        if ($buildRecord->cooler_id) $build['cooler'] = Cooler::find($buildRecord->cooler_id);
        if ($buildRecord->mobo_id) $build['mobo'] = Motherboard::find($buildRecord->mobo_id);
        if ($buildRecord->ram_id) $build['ram'] = Ram::find($buildRecord->ram_id);
        if ($buildRecord->gpu_id) $build['gpu'] = Gpu::find($buildRecord->gpu_id);
        if ($buildRecord->psu_id) $build['psu'] = Psu::find($buildRecord->psu_id);
        if ($buildRecord->case_id) $build['case'] = PcCase::find($buildRecord->case_id);
        
        session(['current_build' => $build]);
        
        return redirect()->route('builder.index')->with('success', "Loaded '{$buildRecord->name}' into your workspace!");
    }

    // 8. Side-by-Side Comparison Matrix
    public function compareView(Request $request)
    {
        $savedBuilds = Build::where('user_id', auth()->id())->latest()->get();
        
        $build1 = null;
        $build2 = null;
        $scores1 = null;
        $scores2 = null;
        
        $relations = ['cpu', 'cooler', 'motherboard', 'ram', 'gpu', 'psu', 'pcCase'];

        if ($request->has('build1') && $request->build1 != '') {
            $build1 = Build::with($relations)->where('user_id', auth()->id())->find($request->build1);
            if ($build1) {
                // Dynamically recalculate to fetch the breakdown arrays
                $parts1 = ['cpu' => $build1->cpu, 'gpu' => $build1->gpu, 'ram' => $build1->ram, 'mobo' => $build1->motherboard, 'cooler' => $build1->cooler, 'psu' => $build1->psu, 'case' => $build1->pcCase];
                $scores1 = $this->validationEngine->calculateScores($parts1);
            }
        }
        
        if ($request->has('build2') && $request->build2 != '') {
            $build2 = Build::with($relations)->where('user_id', auth()->id())->find($request->build2);
            if ($build2) {
                // Dynamically recalculate to fetch the breakdown arrays
                $parts2 = ['cpu' => $build2->cpu, 'gpu' => $build2->gpu, 'ram' => $build2->ram, 'mobo' => $build2->motherboard, 'cooler' => $build2->cooler, 'psu' => $build2->psu, 'case' => $build2->pcCase];
                $scores2 = $this->validationEngine->calculateScores($parts2);
            }
        }

        return view('builder.compare', compact('savedBuilds', 'build1', 'build2', 'scores1', 'scores2'));
    }

    // 9. Delete Build from Database
    public function deleteBuild($id)
    {
        // Ensure the user owns this build before deleting
        $buildRecord = Build::where('user_id', auth()->id())->findOrFail($id);
        
        $buildName = $buildRecord->name;
        $buildRecord->delete();
        
        return redirect()->route('builder.index')->with('success', "Deleted '{$buildName}' from your workspace.");
    }

    
}
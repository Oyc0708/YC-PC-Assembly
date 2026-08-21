<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\ValidationEngine;
use App\Models\{Cpu, Cooler, Motherboard, Ram, Gpu, Psu, PcCase, Storage, Build};

class BuilderController extends Controller
{
    protected ValidationEngine $validationEngine;

    protected array $modelMap = [
        'cpu'     => Cpu::class,
        'cooler'  => Cooler::class,
        'mobo'    => Motherboard::class,
        'ram'     => Ram::class,
        'gpu'     => Gpu::class,
        'psu'     => Psu::class,
        'case'    => PcCase::class,
        'storage' => Storage::class,
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
        $currentBuild = session('current_build', []);

        // -------------------------------------------------------------
        // 1. SMART COMPATIBILITY FILTER TOGGLE
        // -------------------------------------------------------------
        $compatibilityFilter = $request->boolean('compatibility_filter', true);
        
        if ($compatibilityFilter && !empty($currentBuild)) {
            if ($category === 'cpu' && isset($currentBuild['mobo'])) {
                $query->where('socket', $currentBuild['mobo']->socket);
            } elseif ($category === 'mobo' && isset($currentBuild['cpu'])) {
                $query->where('socket', $currentBuild['cpu']->socket);
            } elseif ($category === 'ram' && isset($currentBuild['mobo'])) {
                $query->where('type', $currentBuild['mobo']->ram_type);
            } elseif ($category === 'case' && isset($currentBuild['gpu'])) {
                $gpuLen = (int)($currentBuild['gpu']->length_mm ?? 0);
                $query->where('max_gpu_length_mm', '>=', $gpuLen);
            } elseif ($category === 'psu' && (isset($currentBuild['cpu']) || isset($currentBuild['gpu']))) {
                $estWatts = ((int)($currentBuild['cpu']->tdp ?? 65) + (int)($currentBuild['gpu']->tdp ?? 150) + 65) * 1.25; // 65 for mobo/storage base
                $query->where('wattage', '>=', $estWatts);
            }
            // Note: Storage standardly connects via SATA or M.2, which most boards support, so deep strict filtering is skipped for flexibility.
        }

        // -------------------------------------------------------------
        // 2. TEXT SEARCH & PRICE RANGES
        // -------------------------------------------------------------
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

        // -------------------------------------------------------------
        // 3. MULTI-SELECT CHECKBOX FILTERS
        // -------------------------------------------------------------
        if ($request->filled('manufacturers')) {
            $query->whereIn('manufacturer', (array) $request->manufacturers);
        }

        if (in_array($category, ['cpu', 'mobo']) && $request->filled('sockets')) {
            $query->whereIn('socket', (array) $request->sockets);
        }

        if (in_array($category, ['ram', 'mobo']) && $request->filled('ram_types')) {
            $column = $category === 'ram' ? 'type' : 'ram_type';
            $query->whereIn($column, (array) $request->ram_types);
        }

        if (in_array($category, ['mobo', 'case', 'storage']) && $request->filled('form_factors')) {
            $query->whereIn('form_factor', (array) $request->form_factors);
        }
        
        if ($category === 'storage' && $request->filled('types')) {
            $query->whereIn('type', (array) $request->types);
        }

        // -------------------------------------------------------------
        // 4. NUMERIC SPECIFICATION FILTERS
        // -------------------------------------------------------------
        if ($category === 'cpu') {
            if ($request->filled('min_cores')) $query->where('cores', '>=', (int)$request->min_cores);
            if ($request->filled('max_cores')) $query->where('cores', '<=', (int)$request->max_cores);
            if ($request->filled('max_tdp')) $query->where('tdp', '<=', (int)$request->max_tdp);
        }

        if ($category === 'gpu') {
            if ($request->filled('min_memory')) $query->where('memory', '>=', (int)$request->min_memory);
            if ($request->filled('max_gpu_len')) $query->where('length_mm', '<=', (int)$request->max_gpu_len);
        }

        if ($category === 'psu') {
            if ($request->filled('min_wattage')) $query->where('wattage', '>=', (int)$request->min_wattage);
        }
        
        if ($category === 'storage') {
            if ($request->filled('min_capacity')) $query->where('capacity', '>=', (int)$request->min_capacity);
        }

        // -------------------------------------------------------------
        // 5. EXTRACT DYNAMIC SIDEBAR OPTIONS DIRECTLY FROM DATABASE
        // -------------------------------------------------------------
        $filterOptions = [
            'manufacturers' => $model::whereNotNull('manufacturer')->distinct()->orderBy('manufacturer')->pluck('manufacturer'),
            'price_min'     => (float) ($model::min('price') ?? 0),
            'price_max'     => (float) ($model::max('price') ?? 5000),
        ];

        if (in_array($category, ['cpu', 'mobo'])) {
            $filterOptions['sockets'] = $model::whereNotNull('socket')->distinct()->orderBy('socket')->pluck('socket');
        }
        if (in_array($category, ['ram', 'mobo'])) {
            $col = $category === 'ram' ? 'type' : 'ram_type';
            $filterOptions['ram_types'] = $model::whereNotNull($col)->distinct()->orderBy($col)->pluck($col);
        }
        if (in_array($category, ['mobo', 'case', 'storage'])) {
            $filterOptions['form_factors'] = $model::whereNotNull('form_factor')->distinct()->orderBy('form_factor')->pluck('form_factor');
        }
        if ($category === 'storage') {
            $filterOptions['types'] = $model::whereNotNull('type')->distinct()->orderBy('type')->pluck('type');
        }

        // Eager load the component prices
        $parts = $query->with('prices')->orderBy('price', 'asc')->paginate(20)->withQueryString();

        // Calculate active build summary metrics
        $selectedCount = count(array_filter($currentBuild));
        $totalCost = collect($currentBuild)->filter()->sum('price');
        $estWattage = (int)($currentBuild['cpu']->tdp ?? 65) + (int)($currentBuild['gpu']->tdp ?? 150) + 65; // Added slightly more base wattage

        return view('builder.select', compact(
            'parts',
            'category',
            'filterOptions',
            'compatibilityFilter',
            'currentBuild',
            'selectedCount',
            'totalCost',
            'estWattage'
        ));
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

    // 5. Smart Spec-Driven Auto-Build Engine
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

        // Adjusted Allocations to include Storage
        if ($workload === 'video-editing') {
            $alloc = ['cpu' => 0.30, 'mobo' => 0.10, 'ram' => 0.10, 'gpu' => 0.25, 'storage' => 0.10, 'case' => 0.05, 'psu' => 0.05, 'cooler' => 0.05];
        } elseif ($workload === 'office') {
            $alloc = ['cpu' => 0.35, 'mobo' => 0.15, 'ram' => 0.10, 'gpu' => 0.10, 'storage' => 0.10, 'case' => 0.08, 'psu' => 0.07, 'cooler' => 0.05];
        } else { // Gaming
            $alloc = ['cpu' => 0.20, 'mobo' => 0.10, 'ram' => 0.10, 'gpu' => 0.40, 'storage' => 0.05, 'case' => 0.05, 'psu' => 0.05, 'cooler' => 0.05];
        }

        try {
            $build = [];
            $totalCost = 0;
            $estWattage = 65; // Base wattage for board and drives

            // ==============================================================
            // STEP 1: CPU
            // ==============================================================
            $cpuBudget = $maxBudget * $alloc['cpu'] * 1.2;
            $cpu = Cpu::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$cpuBudget])
                    ->get()
                    ->sortByDesc(function ($c) {
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
            // STEP 2: MOTHERBOARD
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
            // STEP 3: RAM
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
            // STEP 4: STORAGE
            // ==============================================================
            $storageBudget = $maxBudget * $alloc['storage'] * 1.3;
            $storage = Storage::whereRaw('CAST(price AS DECIMAL(10,2)) <= ?', [$storageBudget])
                    ->get()
                    ->sortByDesc(function ($s) {
                        $cap = (int) ($s->capacity ?? 500); 
                        $isNvme = ($s->nvme === 'true' || $s->nvme === true) ? 1 : 0;
                        return ($cap * 10) + ($isNvme * 5000); 
                    })->first();

            if (!$storage) $storage = Storage::orderByRaw('CAST(price AS DECIMAL(10,2)) ASC')->first();
            if (!$storage) throw new \Exception('No compatible storage found.');
            
            $build['storage'] = $storage;
            $totalCost += $storage->price;

            // ==============================================================
            // STEP 5: GPU
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
            // STEP 6: CASE
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
            // STEP 7: PSU
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
            // STEP 8: COOLER
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
            'storage_id'    => isset($build['storage']) ? $build['storage']->id : null, // Added Storage
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
        if ($buildRecord->storage_id) $build['storage'] = Storage::find($buildRecord->storage_id); // Added Storage
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
        
        $relations = ['cpu', 'cooler', 'motherboard', 'ram', 'gpu', 'psu', 'pcCase', 'storage']; // Added Storage relation

        // Safely check and load Build 1
        if ($request->filled('build1')) {
            $build1 = Build::with($relations)->where('user_id', auth()->id())->find($request->build1);
            if ($build1) {
                $parts1 = [
                    'cpu'    => $build1->cpu,
                    'gpu'    => $build1->gpu,
                    'ram'    => $build1->ram,
                    'mobo'   => $build1->motherboard,
                    'cooler' => $build1->cooler,
                    'psu'    => $build1->psu,
                    'case'   => $build1->pcCase,
                    'storage'=> $build1->storage // Added Storage
                ];
                $scores1 = $this->validationEngine->calculateScores($parts1);
            }
        }
        
        // Safely check and load Build 2
        if ($request->filled('build2')) {
            $build2 = Build::with($relations)->where('user_id', auth()->id())->find($request->build2);
            if ($build2) {
                $parts2 = [
                    'cpu'    => $build2->cpu,
                    'gpu'    => $build2->gpu,
                    'ram'    => $build2->ram,
                    'mobo'   => $build2->motherboard,
                    'cooler' => $build2->cooler,
                    'psu'    => $build2->psu,
                    'case'   => $build2->pcCase,
                    'storage'=> $build2->storage // Added Storage
                ];
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

    public function clearBuild()
    {
        // Instantly wipe the current_build array from the user's session
        session()->forget('current_build');
        
        return redirect()->route('builder.index')->with('success', 'Your workspace has been completely cleared.');
    }

}
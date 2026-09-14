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

        $savedBuilds = auth()->check()
            ? Build::where('user_id', auth()->id())->latest()->get() 
            : collect();

        return view('builder.index', compact('currentBuild', 'totalCost', 'compatibility', 'scores', 'savedBuilds'));
    }

    public function selectCategory($category, Request $request)
    {
        if (!array_key_exists($category, $this->modelMap)) {
            abort(404, 'Category not found');
        }

        $model = $this->modelMap[$category];
        $query = $model::query();
        $currentBuild = session('current_build', []);

        // Dynamic Compatibility Filter Toggle
        $compatibilityFilter = $request->has('filter_submitted') 
            ? $request->boolean('compatibility_filter') 
            : true;
        
        if ($compatibilityFilter && !empty($currentBuild)) {
            if ($category === 'cpu' && isset($currentBuild['mobo'])) {
                $socketNum = $this->validationEngine->cleanSocket($currentBuild['mobo']->socket ?? '');
                $query->where('socket', 'LIKE', "%{$socketNum}%");
            } elseif ($category === 'mobo' && isset($currentBuild['cpu'])) {
                $socketNum = $this->validationEngine->cleanSocket($currentBuild['cpu']->socket ?? '');
                $query->where('socket', 'LIKE', "%{$socketNum}%");
            } elseif ($category === 'ram' && isset($currentBuild['mobo'])) {
                $ramType = $this->validationEngine->normalizeStr($currentBuild['mobo']->ram_type ?? '');
                $query->where('type', 'LIKE', "%{$ramType}%");
            } elseif ($category === 'case' && isset($currentBuild['gpu'])) {
                $gpuLen = (int)($currentBuild['gpu']->length_mm ?? 0);
                $query->where('max_gpu_length_mm', '>=', $gpuLen);
            } elseif ($category === 'psu' && (isset($currentBuild['cpu']) || isset($currentBuild['gpu']))) {
                $cpuPeak = $this->validationEngine->getCpuPeakPower($currentBuild['cpu'] ?? null);
                $gpuPeak = $this->validationEngine->getGpuPeakPower($currentBuild['gpu'] ?? null);
                $estWatts = ($cpuPeak + $gpuPeak + 65) * 1.20; // 20% transient headroom buffer
                $query->where('wattage', '>=', $estWatts);
            }
        }

        // Search & Indexed Price Filtering
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('name', 'like', $searchTerm)
                  ->orWhere('manufacturer', 'like', $searchTerm);
            });
        }

        if ($request->filled('min_price')) {
            $query->where('price', '>=', (float) $request->min_price);
        }
        if ($request->filled('max_price')) {
            $query->where('price', '<=', (float) $request->max_price);
        }

        // Checkbox Filtering
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

        // Numeric Spec Filters
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

        // Dynamic Sidebar Options
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

        $parts = $query->with('prices')->orderBy('price', 'asc')->paginate(20)->withQueryString();

        $selectedCount = count(array_filter($currentBuild));
        $totalCost = collect($currentBuild)->filter()->sum('price');
        
        // System peak power including CPU PL2 and GPU transient allowance
        $cpuPeak = $this->validationEngine->getCpuPeakPower($currentBuild['cpu'] ?? null);
        $gpuPeak = $this->validationEngine->getGpuPeakPower($currentBuild['gpu'] ?? null);
        $estWattage = (int) round($cpuPeak + $gpuPeak + (isset($currentBuild['storage']) ? 65 : 55));

        return view('builder.select', compact(
            'parts', 'category', 'filterOptions', 'compatibilityFilter',
            'currentBuild', 'selectedCount', 'totalCost', 'estWattage'
        ));
    }

    public function addPart(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'id'       => 'required|string',
        ]);

        $category = $request->category;

        if (!array_key_exists($category, $this->modelMap)) {
            return back()->with('error', 'Invalid component category specified.');
        }

        $model = $this->modelMap[$category];
        $part = $model::findOrFail($request->id);

        $build = session('current_build', []);
        $build[$category] = $part;
        session(['current_build' => $build]);

        return redirect()->route('builder.index')->with('success', "{$part->name} added to your build!");
    }

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
        'min_budget' => 'required|numeric|min:1000',
        'max_budget' => 'required|numeric|gt:min_budget|max:35000',
        'use_case'   => 'required|string',
        'resolution' => 'required|string',
    ]);

    $maxBudget = (float) $request->max_budget;
    $workload = $request->use_case;

    try {
        // STEP 0: Lock Infrastructure Reserve Floor
        $minCase   = (float) (PcCase::min('price') ?? 40.00);
        $minPsu    = (float) (Psu::min('price') ?? 45.00);
        $minCooler = (float) (Cooler::min('price') ?? 20.00);

        $infraReserve = $minCase + $minPsu + $minCooler;
        $coreBudgetPool = $maxBudget - $infraReserve;

        if ($coreBudgetPool <= 200) {
            throw new \Exception("Budget of RM " . number_format($maxBudget, 2) . " is too low to assemble a compatible build.");
        }

        // Core workload allocation ratios (Office GPU explicitly set to 0.00)
        $coreWeights = match ($workload) {
            'video-editing' => ['cpu' => 0.35, 'mobo' => 0.15, 'ram' => 0.15, 'gpu' => 0.25, 'storage' => 0.10],
            'office'        => ['cpu' => 0.50, 'mobo' => 0.20, 'ram' => 0.15, 'gpu' => 0.00, 'storage' => 0.15],
            default         => ['cpu' => 0.25, 'mobo' => 0.12, 'ram' => 0.10, 'gpu' => 0.45, 'storage' => 0.08],
        };

        $build = [];
        $totalCost = 0;
        $remainingCoreBudget = $coreBudgetPool;
        $remainingCoreWeight = array_sum($coreWeights);

        // Dynamic core part ceiling capped to remaining core budget
        $getCoreBudgetCap = function (string $key, float $multiplier = 1.15) use (&$remainingCoreBudget, &$remainingCoreWeight, $coreWeights) {
            if ($remainingCoreWeight <= 0 || $remainingCoreBudget <= 0) {
                return 0;
            }
            $portion = $coreWeights[$key] / $remainingCoreWeight;
            return min($remainingCoreBudget, $remainingCoreBudget * $portion * $multiplier);
        };

        // STEP 1: CPU (Requires iGPU for Office Workloads)
        $cpuCap = $getCoreBudgetCap('cpu', 1.15);
        
        $cpuQuery = Cpu::where('price', '<=', $cpuCap)
            ->when($workload === 'office', fn($q) => $q->where('has_igpu', true));

        $cpus = (clone $cpuQuery)->orderBy('price', 'desc')->limit(10)->get();

        // Fallback if cap is too restrictive
        if ($cpus->isEmpty()) {
            $cpus = Cpu::where('price', '<=', $remainingCoreBudget)
                ->when($workload === 'office', fn($q) => $q->where('has_igpu', true))
                ->orderBy('price', 'asc')
                ->limit(10)->get();
        }

        if ($cpus->isEmpty()) {
            $errorMsg = $workload === 'office' 
                ? 'No CPUs with integrated graphics found within budget allocation.' 
                : 'No CPUs found within budget allocation.';
            throw new \Exception($errorMsg);
        }

        $cpu = null;
        $mobo = null;
        $moboCap = $getCoreBudgetCap('mobo', 1.15);

        // Iterate through top CPUs to find one that actually has a compatible motherboard in the database
        foreach ($cpus as $potentialCpu) {
            $socketNum = $this->validationEngine->cleanSocket($potentialCpu->socket ?? '');
            
            $potentialMobo = Motherboard::where('price', '<=', $moboCap)
                ->where('socket', 'LIKE', "%{$socketNum}%")
                ->orderBy('price', 'desc')
                ->first() ?? Motherboard::where('socket', 'LIKE', "%{$socketNum}%")
                    ->where('price', '<=', $remainingCoreBudget)
                    ->orderBy('price', 'asc')->first();

            if ($potentialMobo) {
                $cpu = $potentialCpu;
                $mobo = $potentialMobo;
                break;
            }
        }

        if (!$cpu || !$mobo) {
            throw new \Exception("Could not find any compatible CPU & Motherboard combination within budget.");
        }
        
        $build['cpu'] = $cpu;
        $totalCost += (float) $cpu->price;
        $remainingCoreBudget -= (float) $cpu->price;
        $remainingCoreWeight -= $coreWeights['cpu'];
        
        $build['mobo'] = $mobo;
        $totalCost += (float) $mobo->price;
        $remainingCoreBudget -= (float) $mobo->price;
        $remainingCoreWeight -= $coreWeights['mobo'];
        $ramType = $this->validationEngine->normalizeStr($mobo->ram_type ?? '');

        // STEP 3: RAM
        $ramCap = $getCoreBudgetCap('ram', 1.15);
        $ram = Ram::where('price', '<=', $ramCap)
            ->where('type', 'LIKE', "%{$ramType}%")
            ->orderBy('capacity', 'desc')
            ->first() ?? Ram::where('type', 'LIKE', "%{$ramType}%")
                ->where('price', '<=', $remainingCoreBudget)
                ->orderBy('price', 'asc')->first();

        if (!$ram) throw new \Exception("No compatible {$mobo->ram_type} RAM found.");
        
        $build['ram'] = $ram;
        $totalCost += (float) $ram->price;
        $remainingCoreBudget -= (float) $ram->price;
        $remainingCoreWeight -= $coreWeights['ram'];

        // STEP 4: STORAGE
        $storageCap = $getCoreBudgetCap('storage', 1.15);
        $storage = Storage::where('price', '<=', $storageCap)
            ->orderBy('capacity', 'desc')
            ->first() ?? Storage::where('price', '<=', $remainingCoreBudget)->orderBy('price', 'asc')->first();

        if (!$storage) throw new \Exception('No compatible storage found.');
        
        $build['storage'] = $storage;
        $totalCost += (float) $storage->price;
        $remainingCoreBudget -= (float) $storage->price;
        $remainingCoreWeight -= $coreWeights['storage'];

        // STEP 5: GPU (Skipped entirely if allocated weight is 0.00)
        $gpuLength = 0;
        if ($coreWeights['gpu'] > 0.0) {
            $gpuCap = $getCoreBudgetCap('gpu', 1.20);
            $gpu = Gpu::where('price', '<=', $gpuCap)
                ->orderBy('memory', 'desc')
                ->first() ?? Gpu::where('price', '<=', $remainingCoreBudget)->orderBy('price', 'asc')->first();
                
            if ($gpu) {
                $build['gpu'] = $gpu;
                $totalCost += (float) $gpu->price;
                $remainingCoreBudget -= (float) $gpu->price;
                $gpuLength = (int)($gpu->length_mm ?? 0);
            }
            $remainingCoreWeight -= $coreWeights['gpu'];
        }

        // STEP 6: CASE (Bounded to remaining funds minus PSU & Cooler floors)
        $remainingAbsBudget = $maxBudget - $totalCost;
        $caseCap = $remainingAbsBudget - ($minPsu + $minCooler);

        $case = PcCase::where('price', '<=', $caseCap)
            ->where('max_gpu_length_mm', '>=', $gpuLength)
            ->orderBy('price', 'desc')
            ->first() ?? PcCase::where('price', '<=', $caseCap)
                ->where('max_gpu_length_mm', '>=', $gpuLength)
                ->orderBy('price', 'asc')->first();

        if (!$case) throw new \Exception("No case under RM " . number_format(max(0, $caseCap), 2) . " fits a {$gpuLength}mm GPU clearance requirement.");
        
        $build['case'] = $case;
        $totalCost += (float) $case->price;

        // STEP 7: PSU (Bounded to remaining funds minus Cooler floor)
        $remainingAbsBudget = $maxBudget - $totalCost;
        $psuCap = $remainingAbsBudget - $minCooler;

        $cpuPeak = $this->validationEngine->getCpuPeakPower($cpu);
        $gpuPeak = isset($build['gpu']) ? $this->validationEngine->getGpuPeakPower($build['gpu']) : 0;
        $reqWattage = ($cpuPeak + $gpuPeak + 65) * 1.20;

        $psu = Psu::where('price', '<=', $psuCap)
            ->where('wattage', '>=', $reqWattage)
            ->orderBy('wattage', 'desc')
            ->first() ?? Psu::where('price', '<=', $psuCap)
                ->where('wattage', '>=', $reqWattage)
                ->orderBy('price', 'asc')->first();

        if (!$psu) throw new \Exception("No PSU under RM " . number_format(max(0, $psuCap), 2) . " found supplying required peak wattage of " . (int)$reqWattage . "W.");
        
        $build['psu'] = $psu;
        $totalCost += (float) $psu->price;

        // STEP 8: COOLER (Bounded to absolute remaining funds)
        $remainingAbsBudget = $maxBudget - $totalCost;
        $reqThermal = $cpuPeak;

        $cooler = Cooler::where('price', '<=', $remainingAbsBudget)
            ->where('max_tdp', '>=', $reqThermal)
            ->orderBy('max_tdp', 'desc')
            ->first() ?? Cooler::where('price', '<=', $remainingAbsBudget)
                ->where('max_tdp', '>=', $reqThermal)
                ->orderBy('price', 'asc')->first();

        if (!$cooler) throw new \Exception("No cooler under RM " . number_format(max(0, $remainingAbsBudget), 2) . " found capable of dissipating " . (int)$reqThermal . "W.");
        
        $build['cooler'] = $cooler;
        $totalCost += (float) $cooler->price;

        if ($totalCost > $maxBudget) {
            throw new \Exception("Unable to generate complete build under RM " . number_format($maxBudget, 2) . ". Final cost: RM " . number_format($totalCost, 2) . ".");
        }

        session(['current_build' => $build]);

        return redirect()->route('builder.index')->with('success', "Optimized Auto-Build Generated! Total Cost: RM " . number_format($totalCost, 2));

    } catch (\Exception $e) {
        return redirect()->route('builder.index')->with('error', $e->getMessage());
    }
}

    public function saveBuild(Request $request)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Please log in to save your build workspace.');
        }

        $build = session('current_build', []);

        if (empty($build)) {
            return redirect()->route('builder.index')->with('error', 'Your build is empty. Add components before saving!');
        }

        $totalCost = collect($build)->filter()->sum('price');
        $scores = $this->validationEngine->calculateScores($build);

        Build::create([
            'user_id'       => auth()->id(),
            'name'          => 'Custom Build - ' . date('M j, Y'),
            'cpu_id'        => $build['cpu']->id ?? null,
            'cooler_id'     => $build['cooler']->id ?? null,
            'mobo_id'       => $build['mobo']->id ?? null,
            'ram_id'        => $build['ram']->id ?? null,
            'gpu_id'        => $build['gpu']->id ?? null,
            'storage_id'    => $build['storage']->id ?? null,
            'psu_id'        => $build['psu']->id ?? null,
            'case_id'       => $build['case']->id ?? null,
            'total_cost'    => $totalCost,
            'overall_score' => $scores['overall'] ?? 0,
        ]);

        return redirect()->route('builder.index')->with('success', 'Configuration saved to your workspace successfully!');
    }

    public function loadBuild($id)
    {
        $buildRecord = Build::where('user_id', auth()->id())->findOrFail($id);
        
        $build = [];
        if ($buildRecord->cpu_id) $build['cpu'] = Cpu::find($buildRecord->cpu_id);
        if ($buildRecord->cooler_id) $build['cooler'] = Cooler::find($buildRecord->cooler_id);
        if ($buildRecord->mobo_id) $build['mobo'] = Motherboard::find($buildRecord->mobo_id);
        if ($buildRecord->ram_id) $build['ram'] = Ram::find($buildRecord->ram_id);
        if ($buildRecord->gpu_id) $build['gpu'] = Gpu::find($buildRecord->gpu_id);
        if ($buildRecord->storage_id) $build['storage'] = Storage::find($buildRecord->storage_id);
        if ($buildRecord->psu_id) $build['psu'] = Psu::find($buildRecord->psu_id);
        if ($buildRecord->case_id) $build['case'] = PcCase::find($buildRecord->case_id);
        
        session(['current_build' => $build]);
        
        return redirect()->route('builder.index')->with('success', "Loaded '{$buildRecord->name}' into your workspace!");
    }

    public function compareView(Request $request)
    {
        $savedBuilds = auth()->check() ? Build::where('user_id', auth()->id())->latest()->get() : collect();
        
        $build1 = null;
        $build2 = null;
        $scores1 = null;
        $scores2 = null;
        
        $relations = ['cpu', 'cooler', 'motherboard', 'ram', 'gpu', 'psu', 'storage', 'pcCase'];

        if ($request->filled('build1')) {
            $build1 = Build::with($relations)->where('user_id', auth()->id())->find($request->build1);
            if ($build1) {
                $parts1 = [
                    'cpu'     => $build1->cpu,
                    'gpu'     => $build1->gpu,
                    'ram'     => $build1->ram,
                    'mobo'    => $build1->motherboard,
                    'cooler'  => $build1->cooler,
                    'psu'     => $build1->psu,
                    'case'    => $build1->pcCase,
                    'storage' => $build1->storage
                ];
                $scores1 = $this->validationEngine->calculateScores($parts1);
            }
        }
        
        if ($request->filled('build2')) {
            $build2 = Build::with($relations)->where('user_id', auth()->id())->find($request->build2);
            if ($build2) {
                $parts2 = [
                    'cpu'     => $build2->cpu,
                    'gpu'     => $build2->gpu,
                    'ram'     => $build2->ram,
                    'mobo'    => $build2->motherboard,
                    'cooler'  => $build2->cooler,
                    'psu'     => $build2->psu,
                    'case'    => $build2->pcCase,
                    'storage' => $build2->storage
                ];
                $scores2 = $this->validationEngine->calculateScores($parts2);
            }
        }

        return view('builder.compare', compact('savedBuilds', 'build1', 'build2', 'scores1', 'scores2'));
    }

    public function deleteBuild($id)
    {
        $buildRecord = Build::where('user_id', auth()->id())->findOrFail($id);
        $buildName = $buildRecord->name;
        $buildRecord->delete();
        
        return redirect()->route('builder.index')->with('success', "Deleted '{$buildName}' from workspace.");
    }

    public function clearBuild()
    {
        session()->forget('current_build');
        return redirect()->route('builder.index')->with('success', 'Your workspace has been cleared.');
    }

    public function fetchLivePrice(Request $request)
    {
        $request->validate([
            'category' => 'required|string',
            'id'       => 'required|string',
        ]);

        $category = $request->category;
        
        if (!array_key_exists($category, $this->modelMap)) {
            return response()->json(['error' => 'Invalid category'], 400);
        }

        $model = $this->modelMap[$category];
        $part = $model::findOrFail($request->id);

        // Run scraper synchronously
        \App\Jobs\ScrapeComponentPrice::dispatchSync($part, $model);
        
        $part->refresh(); // Reload to get newly saved price

        // Also update the session so subsequent loads have the updated price
        $build = session('current_build', []);
        if (isset($build[$category]) && $build[$category]->id == $part->id) {
            // Flag it as fetched so we don't keep retrying if it's permanently 0
            $part->setAttribute('price_fetched', true);
            $build[$category] = $part;
            session(['current_build' => $build]);
        }

        return response()->json([
            'price' => (float) $part->price,
            'formatted_price' => number_format((float) $part->price, 2)
        ]);
    }
}
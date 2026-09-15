<x-layout>
    <!-- Tab Navigation -->
    <div class="tab-container" role="tablist">
        <button onclick="switchTab('manual')" id="tab-manual" class="tab-btn tab-active" role="tab" aria-selected="true" aria-controls="view-manual">Manual Builder</button>
        <button onclick="switchTab('catalog')" id="tab-catalog" class="tab-btn" role="tab" aria-selected="false" aria-controls="view-catalog">Hardware Catalog</button>
        <button onclick="switchTab('autobuild')" id="tab-autobuild" class="tab-btn" role="tab" aria-selected="false" aria-controls="view-autobuild" style="color: var(--success);">Auto-Build ⚡</button>
        <button onclick="switchTab('saved')" id="tab-saved" class="tab-btn" role="tab" aria-selected="false" aria-controls="view-saved">Saved Builds</button>

        <a href="{{ auth()->check() ? route('builder.compare') : route('login') }}" class="tab-btn" style="text-decoration: none; color: var(--gray-500);">Compare Builds</a>
    </div>

    <!-- VIEW: MANUAL BUILDER -->
    <div id="view-manual" class="tab-view" role="tabpanel" aria-labelledby="tab-manual">
        <div class="builder-layout">
            
            <!-- Left Side: Configuration Slots -->
            <div class="builder-config">
                <div class="card">
                    <!-- Configuration Header & Remove All Action -->
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="margin: 0;">Configuration</h2>
                        
                        @if(count($currentBuild) > 0)
                            <form action="{{ route('builder.clear') }}" method="POST" onsubmit="return confirm('Are you sure you want to clear your entire build?');" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="color: var(--error); border-color: var(--error); padding: 6px 12px; font-size: 12px; display: flex; align-items: center; gap: 5px;">
                                    🗑️ Remove All
                                </button>
                            </form>
                        @endif
                    </div>
                    
                    @if(session('success'))
                        <div style="padding: 10px; background: rgba(0, 255, 102, 0.1); color: var(--success); border-left: 4px solid var(--success); margin-bottom: 20px;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div style="padding: 10px; background: rgba(239, 68, 68, 0.1); color: var(--error); border-left: 4px solid var(--error); margin-bottom: 20px;">
                            {{ session('error') }}
                        </div>
                    @endif

                    <!-- Updated to include 'storage' -->
                    @php
                        $slots = [
                            'cpu' => 'CPU', 'cooler' => 'CPU Cooler', 'mobo' => 'Motherboard',
                            'ram' => 'RAM', 'storage' => 'Storage', 'gpu' => 'Graphics Card', 
                            'psu' => 'Power Supply', 'case' => 'PC Case'
                        ];
                    @endphp

                    @foreach($slots as $key => $label)
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                            <div>
                                <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">{{ $label }}</span><br>
                                @if(isset($currentBuild[$key]))
                                    <strong>{{ $currentBuild[$key]->name }}</strong>
                                    @php $part = $currentBuild[$key]; @endphp
                                    <div style="display: flex; gap: 8px; flex-wrap: wrap; font-size: 11px; color: var(--text-muted); margin-top: 2px;">
                                        @if($key === 'cpu')
                                            <span><strong>Cores:</strong> {{ $part->cores }}</span>
                                            <span><strong>Socket:</strong> {{ $part->socket }}</span>
                                            <span><strong>Base Clock:</strong> {{ $part->base_clock }} GHz</span>
                                            <span><strong>TDP:</strong> {{ $part->tdp }}W</span>
                                        @elseif($key === 'gpu')
                                            <span><strong>VRAM:</strong> {{ $part->memory }} GB</span>
                                            <span><strong>Clock:</strong> {{ $part->clock_speed }} MHz</span>
                                            <span><strong>Length:</strong> {{ $part->length_mm }}mm</span>
                                            <span><strong>TDP:</strong> {{ $part->tdp }}W</span>
                                        @elseif($key === 'mobo')
                                            <span><strong>Socket:</strong> {{ $part->socket }}</span>
                                            <span><strong>RAM:</strong> {{ $part->ram_type }}</span>
                                            <span><strong>Form Factor:</strong> {{ $part->form_factor }}</span>
                                        @elseif($key === 'ram')
                                            <span><strong>Type:</strong> {{ $part->type }}</span>
                                            <span><strong>Capacity:</strong> {{ $part->capacity }} GB</span>
                                            <span><strong>Speed:</strong> {{ $part->speed }} MHz</span>
                                        @elseif($key === 'storage')
                                            <span><strong>Type:</strong> {{ $part->type }}</span>
                                            <span><strong>Capacity:</strong> {{ $part->capacity }} GB</span>
                                            <span><strong>Interface:</strong> {{ $part->interface }}</span>
                                        @elseif($key === 'psu')
                                            <span><strong>Wattage:</strong> {{ $part->wattage }}W</span>
                                        @elseif($key === 'case')
                                            <span><strong>Form Factor:</strong> {{ $part->form_factor }}</span>
                                            <span><strong>Max GPU:</strong> {{ $part->max_gpu_length_mm }}mm</span>
                                        @elseif($key === 'cooler')
                                            <span><strong>Max TDP:</strong> {{ $part->max_tdp }}W</span>
                                        @endif
                                    </div>
                                    <div class="price-container" data-fetch-category="{{ $key }}" data-fetch-id="{{ $currentBuild[$key]->id }}">
                                        @if($currentBuild[$key]->price > 0)
                                            @php
                                                // Load vendor link for static initial display
                                                $vendorLink = $currentBuild[$key]->prices()->where('price', $currentBuild[$key]->price)->first();
                                            @endphp
                                            @if($vendorLink)
                                                <a href="{{ $vendorLink->url }}" target="_blank" class="vendor-link price-val" data-raw-price="{{ $currentBuild[$key]->price }}" style="display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; text-decoration: none; margin-top: 10px; width: 160px; transition: border-color 0.2s;" onmouseover="this.style.borderColor='#555'" onmouseout="this.style.borderColor='var(--border-color)'">
                                                    <span class="vendor-name" style="color: var(--text-main); font-size: 11px; font-weight: bold; margin-bottom: 2px;">{{ $vendorLink->vendor }}</span>
                                                    <span class="price-text" style="color: var(--success); font-size: 14px; font-weight: bold;">RM {{ number_format($currentBuild[$key]->price, 2) }}</span>
                                                </a>
                                            @else
                                                <div class="vendor-link price-val" data-raw-price="{{ $currentBuild[$key]->price }}" style="display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; margin-top: 10px; width: 160px;">
                                                    <span class="vendor-name" style="color: var(--text-muted); font-size: 11px; font-weight: bold; margin-bottom: 2px;">Suggested Price</span>
                                                    <span class="price-text" style="color: var(--success); font-size: 14px; font-weight: bold;">RM {{ number_format($currentBuild[$key]->price, 2) }}</span>
                                                </div>
                                            @endif
                                        @elseif($currentBuild[$key]->getAttribute('price_fetched'))
                                            <div class="vendor-link price-val" data-raw-price="0" style="display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid #4a0000; padding: 8px 12px; border-radius: 6px; margin-top: 10px; width: 160px;">
                                                <span class="vendor-name" style="color: var(--text-muted); font-size: 11px; font-weight: bold; margin-bottom: 2px;">Unavailable</span>
                                                <span class="price-text" style="color: var(--error); font-size: 12px; font-weight: bold;">OUT OF STOCK</span>
                                            </div>
                                        @else
                                            <div class="vendor-link price-val needs-fetch" data-raw-price="0" style="display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; margin-top: 10px; width: 160px; justify-content: center;">
                                                <div style="display: flex; align-items: center; gap: 6px;">
                                                    <svg class="animate-spin" style="width: 14px; height: 14px; color: var(--accent);" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                                    <span class="price-text" style="color: var(--text-muted); font-size: 11px; font-weight: bold;">Fetching price...</span>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @else
                                    <span style="color: var(--text-muted); font-style: italic;">No component selected</span>
                                @endif
                            </div>
                            <div>
                                @if(isset($currentBuild[$key]))
                                    <form action="{{ route('builder.remove') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="category" value="{{ $key }}">
                                        <button type="submit" class="btn btn-secondary" style="color: var(--error); border-color: var(--error);">Remove</button>
                                    </form>
                                @else
                                    <a href="{{ route('builder.select', $key) }}" class="btn btn-primary" style="text-decoration: none;">Choose</a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <!-- Right Side: Build Summary & Engine -->
            <div class="summary-sidebar">
                <div class="card">
                    <h2 style="margin-top: 0;">Build Summary</h2>
                    @php
                        $missingPricesCount = collect($currentBuild)->filter(fn($p) => $p && $p->price <= 0)->count();
                    @endphp
                    <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; margin-bottom: 5px;">
                        <span>Est. Total</span>
                        <span style="color: var(--success);">RM {{ number_format($totalCost, 2) }}</span>
                    </div>
                    @if($missingPricesCount > 0)
                        <div style="text-align: right; font-size: 12px; color: var(--error); margin-bottom: 20px;">
                            ⚠️ Total excludes {{ $missingPricesCount }} {{ $missingPricesCount > 1 ? 'items' : 'item' }} without pricing data
                        </div>
                    @else
                        <div style="margin-bottom: 20px;"></div>
                    @endif

                    <!-- SCORE GAUGE SYSTEM -->
                    @if(count($currentBuild) > 0 && isset($scores))
                        <div class="score-card" style="display: flex; justify-content: space-between; align-items: center; background: var(--bg-surface); padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 20px;">
                            
                            <div class="score-info">
                                <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px; display: flex; align-items: center;">
                                    Estimated Score
                                    <div class="info-tooltip">
                                        i
                                        <div class="tooltip-content" style="width: 320px; text-align: left;">
                                            <strong style="color: var(--accent-blue); display: block; margin-bottom: 8px; font-size: 14px;">Heuristic Score Breakdown</strong>
                                            @if(isset($scores['breakdown']['error']))
                                                {{ $scores['breakdown']['error'] }}
                                            @else
                                                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 8px;">
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores['breakdown']['gpu_contribution'] ?? '' }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores['breakdown']['gpu_math'] ?? '' }}</div>
                                                    
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores['breakdown']['cpu_contribution'] ?? '' }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores['breakdown']['cpu_math'] ?? '' }}</div>
                                                    
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores['breakdown']['ram_contribution'] ?? '' }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores['breakdown']['ram_math'] ?? '' }}</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px; margin-bottom: 6px; font-size: 11px;">
                                                    <div style="color: var(--text-main); font-weight: bold;">Gaming Focus:</div>
                                                    <div style="color: var(--text-muted);">(GPU × 65%) + (CPU × 25%) + (RAM × 10%)</div>
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">Productivity Focus:</div>
                                                    <div style="color: var(--text-muted);">(CPU × 50%) + (RAM × 30%) + (GPU × 20%)</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px;">
                                                    <strong style="color: {{ ($scores['breakdown']['multiplier'] ?? 1) < 1 ? 'var(--gray-500)' : 'var(--success)' }}; display: block; margin-bottom: 2px;">
                                                        System Balance Impact
                                                    </strong>
                                                    <span style="color: var(--text-muted);">{{ $scores['breakdown']['balance'] ?? 'Balanced' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div style="color: var(--accent); font-size: 12px; font-weight: bold; margin-bottom: 5px; padding: 2px 6px; border: 1px solid var(--accent); display: inline-block; border-radius: 4px;">{{ $scores['tier'] ?? 'N/A' }}</div>
                                <div style="font-size: 24px; font-weight: bold; color: var(--text-main); margin-bottom: 5px;">{{ number_format($scores['overall'] ?? 0) }} pts</div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Gaming: <strong style="color: #fff;">{{ number_format($scores['gaming'] ?? 0) }}</strong><br>
                                    Prod: <strong style="color: #fff;">{{ number_format($scores['productivity'] ?? 0) }}</strong>
                                </div>
                            </div>

                            <div class="score-gauge" style="position: relative; width: 80px; height: 80px;">
                                @php
                                    $circumference = 2 * pi() * 34;
                                    $offset = $circumference - (($scores['percentage'] ?? 0) / 100) * $circumference;
                                @endphp
                                
                                <svg width="80" height="80" style="transform: rotate(-90deg);">
                                    <circle cx="40" cy="40" r="34" stroke="#222" stroke-width="8" fill="transparent" />
                                    <circle cx="40" cy="40" r="34" stroke="var(--accent)" stroke-width="8" fill="transparent"
                                            stroke-dasharray="{{ $circumference }}"
                                            stroke-dashoffset="{{ $circumference }}"
                                            stroke-linecap="round"
                                            style="animation: fillGauge 1.5s ease-out forwards; --target-offset: {{ $offset }};" />
                                </svg>
                                
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; color: var(--text-main);">
                                    {{ $scores['percentage'] ?? 0 }}%
                                </div>
                            </div>
                        </div>
                    @endif

                    <h3 style="border-top: 1px solid var(--border-color); padding-top: 15px;">Constraint Engine</h3>
                    
                    @if(count($currentBuild) > 0 && isset($compatibility))
                        @if($compatibility['is_valid'] && count($compatibility['warnings']) === 0)
                            <div style="color: var(--success); padding: 10px; border: 1px solid var(--success); border-radius: 4px;">
                                ✓ SYSTEM OPTIMAL (Est. {{ $compatibility['estimated_wattage'] }}W)
                            </div>
                        @else
                            @foreach($compatibility['issues'] as $issue)
                                <div style="color: var(--error); margin-bottom: 10px;">❌ {{ $issue }}</div>
                            @endforeach
                            @foreach($compatibility['warnings'] as $warning)
                                <div style="color: orange; margin-bottom: 10px;">⚠️ {{ $warning }}</div>
                            @endforeach
                        @endif
                    @else
                        <div style="color: var(--text-muted);">Add parts to run diagnostics.</div>
                    @endif

                    <!-- ADVANCED HEURISTICS & ANALYTICS -->
                    @if(isset($compatibility['power_analytics']) || isset($compatibility['bottleneck']))
                        <h3 style="border-top: 1px solid var(--border-color); padding-top: 15px; margin-top: 20px;">System Analytics</h3>
                        
                        <div style="display: flex; flex-direction: column; gap: 15px;">
                            
                            <!-- Bottleneck Analyst -->
                            @if(isset($compatibility['bottleneck']))
                                <div style="background: var(--bg-main); padding: 12px; border-radius: 6px; border: 1px solid var(--border-color); border-left: 4px solid {{ $compatibility['bottleneck']['color'] }};">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: bold;">Compute Balance</span>
                                        <span style="font-size: 12px; font-weight: bold; color: {{ $compatibility['bottleneck']['color'] }};">{{ $compatibility['bottleneck']['status'] }}</span>
                                    </div>
                                    <div style="font-size: 12px; color: var(--text-main);">
                                        {{ $compatibility['bottleneck']['message'] }}
                                    </div>
                                </div>
                            @endif

                            <!-- Power Efficiency Curve -->
                            @if(isset($compatibility['power_analytics']))
                                <div style="background: var(--bg-main); padding: 12px; border-radius: 6px; border: 1px solid var(--border-color);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                        <span style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: bold;">PSU Load Curve</span>
                                        <span style="font-size: 12px; font-weight: bold; color: {{ $compatibility['power_analytics']['color'] }};">{{ $compatibility['power_analytics']['load_percentage'] }}% Load</span>
                                    </div>
                                    
                                    <div style="width: 100%; background: #2d2d2d; height: 6px; border-radius: 3px; margin-bottom: 8px; overflow: hidden; display: flex;">
                                        <div style="width: {{ min($compatibility['power_analytics']['load_percentage'], 100) }}%; background-color: {{ $compatibility['power_analytics']['color'] }}; transition: width 0.5s ease;"></div>
                                    </div>
                                    
                                    <div style="font-size: 11px; color: var(--text-muted);">
                                        {{ $compatibility['power_analytics']['message'] }}
                                    </div>
                                </div>
                            @endif

                        </div>
                    @endif

                    <!-- Save Configuration Action -->
                    @auth
                        <form action="{{ route('builder.save') }}" method="POST" style="margin-top: 20px;">
                            @csrf
                            <button type="submit" class="btn btn-primary" style="width: 100%;">💾 Save Configuration</button>
                        </form>
                    @else
                        <button onclick="alert('Please log in or create an account to save your configurations.')" class="btn btn-primary" style="width: 100%; margin-top: 20px; opacity: 0.7;">
                            💾 Login to Save
                        </button>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW: HARDWARE CATALOG -->
    <div id="view-catalog" class="tab-view" role="tabpanel" aria-labelledby="tab-catalog" style="display: none;">
        <div class="card">
            <h2>Live Database Catalog</h2>
            <p style="color: var(--text-muted);">Select a category to browse components.</p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <!-- Updated to include 'storage' -->
                @foreach(['cpu', 'cooler', 'mobo', 'ram', 'storage', 'gpu', 'psu', 'case'] as $cat)
                    <a href="{{ route('builder.select', $cat) }}" class="btn btn-secondary" style="text-decoration: none; text-transform: uppercase;">Browse {{ $cat }}s</a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- VIEW: AUTO-BUILD -->
    <div id="view-autobuild" class="tab-view" role="tabpanel" aria-labelledby="tab-autobuild" style="display: none;">
        <div class="card" style="padding: 40px; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 40px;">
                <h1 style="font-size: 40px; margin: 0; color: var(--accent);">⚡</h1>
                <h2>Auto-Build Generator</h2>
                <p style="color: var(--text-muted);">
                    Set your target budget range and workload. Our combinatorial solver will query the database to find the highest-performing, bottleneck-free configuration within your strict constraints.
                </p>
            </div>

            <form method="POST" action="{{ route('builder.autobuild') }}">
                @csrf
                <div class="builder-layout" style="grid-template-columns: 1fr 1fr; margin-bottom: 20px;">
                    <div class="form-group">
                        <label class="form-label">Primary Workload</label>
                        <select name="use_case" class="form-input">
                            <option value="gaming">Gaming</option>
                            <option value="video-editing">Video Editing & Rendering</option>
                            <option value="office">General Office & Web</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label class="form-label">Target Resolution</label>
                        <select name="resolution" class="form-input">
                            <option value="1080p">1080p (FHD)</option>
                            <option value="2k">1440p (2K / QHD)</option>
                            <option value="4k">2160p (4K / UHD)</option>
                        </select>
                    </div>
                </div>

                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label" style="font-size: 16px; text-align: center;">Target Budget Range (RM)</label>
                    <div style="display: flex; gap: 20px; justify-content: center; align-items: center; max-width: 500px; margin: 0 auto;">
                        <div style="width: 100%;">
                            <label class="form-label" style="font-size: 12px;">Minimum</label>
                            <input type="number" name="min_budget" min="1500" max="30000" step="100" value="4000" class="form-input" style="font-size: 20px; text-align: center; font-weight: bold; color: var(--success);">
                        </div>
                        <div style="font-size: 24px; color: var(--text-muted); padding-top: 20px;">-</div>
                        <div style="width: 100%;">
                            <label class="form-label" style="font-size: 12px;">Maximum</label>
                            <input type="number" name="max_budget" min="2000" max="35000" step="100" value="6500" class="form-input" style="font-size: 20px; text-align: center; font-weight: bold; color: var(--gray-500);">
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn btn-primary" style="font-size: 16px; padding: 12px 30px;">Generate Optimized Build</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW: SAVED BUILDS WORKSPACE -->
    <div id="view-saved" class="tab-view" role="tabpanel" aria-labelledby="tab-saved" style="display: none;">
        <div class="card">
            <h2 style="margin-top: 0;">Your Workspace</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Manage, load, and compare your saved configurations.</p>
            
            @auth
                @if(isset($savedBuilds) && $savedBuilds->count() > 0)
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
                        @foreach($savedBuilds as $saved)
                            <div class="card" style="margin-bottom: 0; background: var(--bg-main); border: 1px solid var(--border-color); display: flex; flex-direction: column;">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div>
                                        <h3 style="margin: 0; color: var(--accent-blue); font-size: 18px;">{{ $saved->name }}</h3>
                                        <p style="font-size: 12px; color: var(--text-muted); margin: 4px 0 0 0;">Saved on {{ $saved->created_at->format('M j, Y') }}</p>
                                    </div>
                                    <div style="background: var(--surface); padding: 4px 10px; border-radius: 4px; font-weight: bold; border: 1px solid var(--border-color);">
                                        #{{ $saved->id }}
                                    </div>
                                </div>
                                
                                <div style="display: flex; justify-content: space-between; margin-bottom: 20px; border-top: 1px solid var(--border-color); border-bottom: 1px solid var(--border-color); padding: 15px 0; flex-grow: 1;">
                                    <div>
                                        <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Est. Cost</div>
                                        <strong style="color: var(--success); font-size: 18px;">RM {{ number_format($saved->total_cost, 2) }}</strong>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Perf. Score</div>
                                        <strong style="color: var(--text-main); font-size: 18px;">{{ number_format($saved->overall_score) }}</strong>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 10px;">
                                    <form action="{{ route('builder.load', $saved->id) }}" method="POST" style="flex: 1; margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 10px;">Load Build</button>
                                    </form>
                                    
                                    <a href="{{ route('builder.compare', ['build1' => $saved->id]) }}" class="btn btn-secondary" style="flex: 1; padding: 10px; text-decoration: none; text-align: center; display: inline-block;">Compare</a>
                                    
                                    <form action="{{ route('builder.delete', $saved->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to permanently delete this build?');">
                                        @csrf
                                        <button type="submit" class="btn btn-secondary" style="color: var(--error); border-color: var(--error); padding: 10px; height: 100%; display: flex; align-items: center; justify-content: center;" title="Delete Build">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 40px; background: var(--bg-main); border-radius: 8px;">
                        <h3 style="color: var(--text-muted);">No saved builds yet.</h3>
                        <p style="color: var(--text-muted);">Head over to the Manual Builder or Auto-Build to create your first configuration.</p>
                        <button onclick="switchTab('manual')" class="btn btn-primary" style="margin-top: 15px;">Start Building</button>
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 40px; background: var(--bg-main); border-radius: 8px;">
                    <h3 style="color: var(--text-muted);">Guest Workspace</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Please log in or register to permanently save your PC builds across devices.</p>
                    <a href="{{ route('login') }}" class="btn btn-primary" style="text-decoration: none;">Log In</a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Dynamic Styles & JavaScript -->
    <style>
        @keyframes fillGauge {
            to { stroke-dashoffset: var(--target-offset); }
        }
    </style>

    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-view').forEach(view => view.style.display = 'none');
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
                btn.setAttribute('aria-selected', 'false');
            });

            const targetView = document.getElementById('view-' + tabId);
            const targetBtn = document.getElementById('tab-' + tabId);

            if (targetView && targetBtn) {
                targetView.style.display = 'block';
                targetBtn.classList.add('tab-active');
                targetBtn.setAttribute('aria-selected', 'true');
                window.location.hash = tabId;
            }
        }

        // Auto-activate tab from URL hash on page load
        document.addEventListener("DOMContentLoaded", function() {
            const hash = window.location.hash.replace('#', '');
            if (hash && document.getElementById('view-' + hash)) {
                switchTab(hash);
            }
        });
    </script>

    <!-- WELCOME TOAST MODULE -->
    @php
        $showWelcome = !session()->has('has_been_welcomed_builder');
        if ($showWelcome) {
            session(['has_been_welcomed_builder' => true]);
            $userName = auth()->check() ? auth()->user()->name : 'User';
        }
    @endphp

    @if($showWelcome)
        <div id="welcome-toast"
            style="position: fixed; bottom: 30px; right: 30px; background-color: var(--neon-green, #4ade80); color: #111; padding: 16px 28px; border-radius: 8px; font-weight: bold; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.5); z-index: 9999; transition: opacity 0.5s ease, transform 0.5s ease; transform: translateY(0);">
            Welcome back, {{ $userName }}!
        </div>

        <script>
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    const toast = document.getElementById('welcome-toast');
                    if (toast) {
                        toast.style.opacity = '0';
                        toast.style.transform = 'translateY(20px)';
                        setTimeout(() => toast.remove(), 500);
                    }
                }, 4000);
            });
        </script>
    @endif

    <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    </style>

    <script>
        document.addEventListener("DOMContentLoaded", async function() {
            const fetchItems = Array.from(document.querySelectorAll('.needs-fetch'));
            
            for (const item of fetchItems) {
                const container = item.closest('.price-container');
                const category = container.getAttribute('data-fetch-category');
                const id = container.getAttribute('data-fetch-id');
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') 
                                || document.querySelector('input[name="_token"]')?.value;

                try {
                    const response = await fetch("{{ route('builder.fetch-live-price') }}", {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': csrfToken
                        },
                        body: JSON.stringify({ category: category, id: id })
                    });
                    
                    const data = await response.json();
                    
                    if (data.price > 0) {
                        if (data.url && data.url !== '#') {
                            const newEl = document.createElement('a');
                            newEl.href = data.url;
                            newEl.target = '_blank';
                            newEl.className = 'vendor-link price-val';
                            newEl.setAttribute('data-raw-price', data.price);
                            newEl.style.cssText = 'display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; text-decoration: none; margin-top: 10px; width: 160px; transition: border-color 0.2s;';
                            newEl.innerHTML = `
                                <span class="vendor-name" style="color: var(--text-main); font-size: 11px; font-weight: bold; margin-bottom: 2px;">${data.vendor}</span>
                                <span class="price-text" style="color: var(--success); font-size: 14px; font-weight: bold;">RM ${data.formatted_price}</span>
                            `;
                            newEl.onmouseover = function() { this.style.borderColor = '#555'; };
                            newEl.onmouseout = function() { this.style.borderColor = 'var(--border-color)'; };
                            item.replaceWith(newEl);
                        } else {
                            const newEl = document.createElement('div');
                            newEl.className = 'vendor-link price-val';
                            newEl.setAttribute('data-raw-price', data.price);
                            newEl.style.cssText = 'display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid var(--border-color); padding: 8px 12px; border-radius: 6px; margin-top: 10px; width: 160px;';
                            newEl.innerHTML = `
                                <span class="vendor-name" style="color: var(--text-muted); font-size: 11px; font-weight: bold; margin-bottom: 2px;">Suggested Price</span>
                                <span class="price-text" style="color: var(--success); font-size: 14px; font-weight: bold;">RM ${data.formatted_price}</span>
                            `;
                            item.replaceWith(newEl);
                        }
                        updateTotalCost();
                    } else {
                        const newEl = document.createElement('div');
                        newEl.className = 'vendor-link price-val';
                        newEl.setAttribute('data-raw-price', '0');
                        newEl.style.cssText = 'display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid #4a0000; padding: 8px 12px; border-radius: 6px; margin-top: 10px; width: 160px;';
                        newEl.innerHTML = `
                            <span class="vendor-name" style="color: var(--text-muted); font-size: 11px; font-weight: bold; margin-bottom: 2px;">Unavailable</span>
                            <span class="price-text" style="color: var(--error); font-size: 12px; font-weight: bold;">OUT OF STOCK</span>
                        `;
                        item.replaceWith(newEl);
                    }
                } catch (error) {
                    console.error('Error fetching price:', error);
                    item.innerHTML = 'Failed to fetch price';
                }
            }

            function updateTotalCost() {
                let total = 0;
                document.querySelectorAll('.price-val').forEach(el => {
                    let price = parseFloat(el.getAttribute('data-raw-price'));
                    if (!isNaN(price) && price > 0) {
                        total += price;
                    }
                });
                
                // Format the total with commas
                const formattedTotal = total.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                
                // Update the total display
                const totalDisplay = document.querySelector('.summary-sidebar span[style*="color: var(--success)"]');
                if (totalDisplay) {
                    totalDisplay.innerText = 'RM ' + formattedTotal;
                }
            }
        });
    </script>
</x-layout>
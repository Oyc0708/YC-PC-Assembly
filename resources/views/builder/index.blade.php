<x-layout>
    <!-- Tab Navigation -->
    <div class="tab-container">
        <button onclick="switchTab('manual')" id="tab-manual" class="tab-btn tab-active">Manual Builder</button>
        <button onclick="switchTab('catalog')" id="tab-catalog" class="tab-btn">Hardware Catalog</button>
        <button onclick="switchTab('autobuild')" id="tab-autobuild" class="tab-btn" style="color: var(--neon-green);">Auto-Build ⚡</button>
        <button onclick="switchTab('saved')" id="tab-saved" class="tab-btn">Saved Builds</button>

        @auth
            <a href="{{ route('builder.compare') }}" class="tab-btn" style="text-decoration: none; color: var(--neon-orange);">Compare Builds</a>
        @else
            <a href="{{ route('login') }}" class="tab-btn" style="text-decoration: none; color: var(--neon-orange);">Compare Builds</a>
        @endauth
        
    </div>

    <!-- VIEW: MANUAL BUILDER -->
    <div id="view-manual" class="tab-view">
        <div class="builder-layout">
            
            <!-- Left Side: Configuration Slots -->
            <div class="builder-config">
                <div class="card">
                    <h2 style="margin-top: 0;">Configuration</h2>
                    
                    @if(session('success'))
                        <div style="padding: 10px; background: rgba(0, 255, 102, 0.1); color: var(--neon-green); border-left: 4px solid var(--neon-green); margin-bottom: 20px;">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div style="padding: 10px; background: rgba(239, 68, 68, 0.1); color: var(--neon-red); border-left: 4px solid var(--neon-red); margin-bottom: 20px;">
                            {{ session('error') }}
                        </div>
                    @endif

                    @php
                        $slots = [
                            'cpu' => 'CPU', 'cooler' => 'CPU Cooler', 'mobo' => 'Motherboard',
                            'ram' => 'RAM', 'gpu' => 'Graphics Card', 'psu' => 'Power Supply', 'case' => 'PC Case'
                        ];
                    @endphp

                    @foreach($slots as $key => $label)
                        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding: 15px 0;">
                            <div>
                                <span style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">{{ $label }}</span><br>
                                @if(isset($currentBuild[$key]))
                                    <strong>{{ $currentBuild[$key]->name }}</strong>
                                    <div style="color: var(--neon-green); font-size: 14px; margin-top: 4px;">RM {{ number_format($currentBuild[$key]->price, 2) }}</div>
                                @else
                                    <span style="color: #666; font-style: italic;">No component selected</span>
                                @endif
                            </div>
                            <div>
                                @if(isset($currentBuild[$key]))
                                    <form action="{{ route('builder.remove') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="category" value="{{ $key }}">
                                        <button type="submit" class="btn-secondary" style="color: var(--neon-red); border-color: var(--neon-red);">Remove</button>
                                    </form>
                                @else
                                    <a href="{{ route('builder.select', $key) }}" class="btn-primary" style="text-decoration: none;">Choose</a>
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
                    <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; margin-bottom: 20px;">
                        <span>Est. Total</span>
                        <span style="color: var(--neon-green);">RM {{ number_format($totalCost, 2) }}</span>
                    </div>

                    <!-- SCORE GAUGE SYSTEM -->
                    @if(count($currentBuild) > 0 && isset($scores))
                        <div class="score-card" style="display: flex; justify-content: space-between; align-items: center; background: #111; padding: 20px; border-radius: 8px; border: 1px solid var(--border-color); margin-bottom: 20px;">
                            
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
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores['breakdown']['gpu_contribution'] }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores['breakdown']['gpu_math'] }}</div>
                                                    
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores['breakdown']['cpu_contribution'] }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores['breakdown']['cpu_math'] }}</div>
                                                    
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores['breakdown']['ram_contribution'] }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores['breakdown']['ram_math'] }}</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px; margin-bottom: 6px; font-size: 11px;">
                                                    <div style="color: var(--text-main); font-weight: bold;">Gaming Focus:</div>
                                                    <div style="color: var(--text-muted);">(GPU × 65%) + (CPU × 25%) + (RAM × 10%)</div>
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">Productivity Focus:</div>
                                                    <div style="color: var(--text-muted);">(CPU × 50%) + (RAM × 30%) + (GPU × 20%)</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px;">
                                                    <strong style="color: {{ $scores['breakdown']['multiplier'] < 1 ? 'var(--neon-orange)' : 'var(--neon-green)' }}; display: block; margin-bottom: 2px;">
                                                        System Balance Impact
                                                    </strong>
                                                    <span style="color: var(--text-muted);">{{ $scores['breakdown']['balance'] }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div style="color: var(--neon-blue); font-size: 12px; font-weight: bold; margin-bottom: 5px; padding: 2px 6px; border: 1px solid var(--neon-blue); display: inline-block; border-radius: 4px;">{{ $scores['tier'] }}</div>
                                <div style="font-size: 24px; font-weight: bold; color: var(--text-main); margin-bottom: 5px;">{{ number_format($scores['overall']) }} pts</div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Gaming: <strong style="color: #fff;">{{ number_format($scores['gaming']) }}</strong><br>
                                    Prod: <strong style="color: #fff;">{{ number_format($scores['productivity']) }}</strong>
                                </div>
                            </div>

                            <div class="score-gauge" style="position: relative; width: 80px; height: 80px;">
                                @php
                                    $circumference = 2 * pi() * 34;
                                    $offset = $circumference - ($scores['percentage'] / 100) * $circumference;
                                @endphp
                                
                                <svg width="80" height="80" style="transform: rotate(-90deg);">
                                    <circle cx="40" cy="40" r="34" stroke="#222" stroke-width="8" fill="transparent" />
                                    <circle cx="40" cy="40" r="34" stroke="var(--neon-blue)" stroke-width="8" fill="transparent"
                                            stroke-dasharray="{{ $circumference }}"
                                            stroke-dashoffset="{{ $circumference }}" 
                                            stroke-linecap="round"
                                            style="animation: fillGauge 1.5s ease-out forwards;" />
                                </svg>
                                
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; color: var(--text-main);">
                                    {{ $scores['percentage'] }}%
                                </div>
                                
                                <style>
                                    @keyframes fillGauge {
                                        to { stroke-dashoffset: {{ $offset }}; }
                                    }
                                </style>
                            </div>
                        </div>
                    @endif

                    <h3 style="border-top: 1px solid var(--border-color); padding-top: 15px;">Constraint Engine</h3>
                    
                    @if(count($currentBuild) > 0 && isset($compatibility))
                        @if($compatibility['is_valid'] && count($compatibility['warnings']) === 0)
                            <div style="color: var(--neon-green); padding: 10px; border: 1px solid var(--neon-green); border-radius: 4px;">
                                ✓ SYSTEM OPTIMAL (Est. {{ $compatibility['estimated_wattage'] }}W)
                            </div>
                        @else
                            @foreach($compatibility['issues'] as $issue)
                                <div style="color: var(--neon-red); margin-bottom: 10px;">❌ {{ $issue }}</div>
                            @endforeach
                            @foreach($compatibility['warnings'] as $warning)
                                <div style="color: orange; margin-bottom: 10px;">⚠️ {{ $warning }}</div>
                            @endforeach
                        @endif
                    @else
                        <div style="color: var(--text-muted);">Add parts to run diagnostics.</div>
                    @endif

                    <!-- Save Configuration Action -->
                    @auth
                        <form action="{{ route('builder.save') }}" method="POST" style="margin-top: 20px;">
                            @csrf
                            <button type="submit" class="btn-primary" style="width: 100%;">💾 Save Configuration</button>
                        </form>
                    @else
                        <button onclick="alert('Please log in or create an account to save your configurations.')" class="btn-primary" style="width: 100%; margin-top: 20px; opacity: 0.7;">
                            💾 Login to Save
                        </button>
                    @endauth
                </div>
            </div>
        </div>
    </div>

    <!-- VIEW: HARDWARE CATALOG -->
    <div id="view-catalog" class="tab-view" style="display: none;">
        <div class="card">
            <h2>Live Database Catalog</h2>
            <p style="color: var(--text-muted);">Select a category to browse components.</p>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                @foreach(['cpu', 'cooler', 'mobo', 'ram', 'gpu', 'psu', 'case'] as $cat)
                    <a href="{{ route('builder.select', $cat) }}" class="btn-secondary" style="text-decoration: none; text-transform: uppercase;">Browse {{ $cat }}s</a>
                @endforeach
            </div>
        </div>
    </div>

    <!-- VIEW: AUTO-BUILD -->
    <div id="view-autobuild" class="tab-view" style="display: none;">
        <div class="card" style="padding: 40px; max-width: 800px; margin: 0 auto;">
            <div style="text-align: center; margin-bottom: 40px;">
                <h1 style="font-size: 40px; margin: 0; color: var(--neon-blue);">⚡</h1>
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

                <!-- Budget Range Inputs -->
                <div class="form-group" style="margin-top: 20px;">
                    <label class="form-label" style="font-size: 16px; text-align: center;">Target Budget Range (RM)</label>
                    <div style="display: flex; gap: 20px; justify-content: center; align-items: center; max-width: 500px; margin: 0 auto;">
                        <div style="width: 100%;">
                            <label class="form-label" style="font-size: 12px;">Minimum</label>
                            <input type="number" name="min_budget" min="1500" max="30000" step="100" value="4000" class="form-input" style="font-size: 20px; text-align: center; font-weight: bold; color: var(--neon-green);">
                        </div>
                        <div style="font-size: 24px; color: var(--text-muted); padding-top: 20px;">-</div>
                        <div style="width: 100%;">
                            <label class="form-label" style="font-size: 12px;">Maximum</label>
                            <input type="number" name="max_budget" min="2000" max="35000" step="100" value="6500" class="form-input" style="font-size: 20px; text-align: center; font-weight: bold; color: var(--neon-orange);">
                        </div>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 40px;">
                    <button type="submit" class="btn-primary" style="font-size: 16px; padding: 12px 30px;">Generate Optimized Build</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW: SAVED BUILDS WORKSPACE -->
    <div id="view-saved" class="tab-view" style="display: none;">
        <div class="card">
            <h2 style="margin-top: 0;">Your Workspace</h2>
            <p style="color: var(--text-muted); margin-bottom: 30px;">Manage, load, and compare your saved configurations.</p>
            
            @auth
                @if(isset($savedBuilds) && $savedBuilds->count() > 0)
                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px;">
                        @foreach($savedBuilds as $saved)
                            <div class="card" style="margin-bottom: 0; background: var(--bg-dark); border: 1px solid var(--border-color); display: flex; flex-direction: column;">
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
                                        <strong style="color: var(--neon-green); font-size: 18px;">RM {{ number_format($saved->total_cost, 2) }}</strong>
                                    </div>
                                    <div style="text-align: right;">
                                        <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">Perf. Score</div>
                                        <strong style="color: var(--text-main); font-size: 18px;">{{ number_format($saved->overall_score) }}</strong>
                                    </div>
                                </div>
                                
                                <div style="display: flex; gap: 10px;">
                                    <form action="{{ route('builder.load', $saved->id) }}" method="POST" style="flex: 1; margin: 0;">
                                        @csrf
                                        <button type="submit" class="btn-primary" style="width: 100%; padding: 10px;">Load Build</button>
                                    </form>
                                    
                                    <a href="{{ route('builder.compare', ['build1' => $saved->id]) }}" class="btn-secondary" style="flex: 1; padding: 10px; text-decoration: none; text-align: center; display: inline-block;">Compare</a>
                                    
                                    <!-- NEW: Delete Button -->
                                    <form action="{{ route('builder.delete', $saved->id) }}" method="POST" style="margin: 0;" onsubmit="return confirm('Are you sure you want to permanently delete this build?');">
                                        @csrf
                                        <button type="submit" class="btn-secondary" style="color: var(--neon-red); border-color: var(--neon-red); padding: 10px; height: 100%; display: flex; align-items: center; justify-content: center;" title="Delete Build">
                                            🗑️
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div style="text-align: center; padding: 40px; background: var(--bg-dark); border-radius: 8px;">
                        <h3 style="color: var(--text-muted);">No saved builds yet.</h3>
                        <p style="color: var(--text-muted);">Head over to the Manual Builder or Auto-Build to create your first configuration.</p>
                        <button onclick="switchTab('manual')" class="btn-primary" style="margin-top: 15px;">Start Building</button>
                    </div>
                @endif
            @else
                <div style="text-align: center; padding: 40px; background: var(--bg-dark); border-radius: 8px;">
                    <h3 style="color: var(--text-muted);">Guest Workspace</h3>
                    <p style="color: var(--text-muted); margin-bottom: 20px;">Please log in or register to permanently save your PC builds across devices.</p>
                    <a href="/login" class="btn-primary" style="text-decoration: none;">Log In</a>
                </div>
            @endauth
        </div>
    </div>

    <!-- Simple JavaScript for Tab Switching -->
    <script>
        function switchTab(tabId) {
            document.querySelectorAll('.tab-view').forEach(view => {
                view.style.display = 'none';
            });
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
            });
            document.getElementById('view-' + tabId).style.display = 'block';
            document.getElementById('tab-' + tabId).classList.add('tab-active');
        }
    </script>
</x-layout>
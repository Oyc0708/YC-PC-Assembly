<x-layout>
    <!-- Tab Navigation -->
    <div class="tab-container">
        <button onclick="switchTab('manual')" id="tab-manual" class="tab-btn tab-active">Manual Builder</button>
        <button onclick="switchTab('catalog')" id="tab-catalog" class="tab-btn">Hardware Catalog</button>
        <button onclick="switchTab('autobuild')" id="tab-autobuild" class="tab-btn" style="color: var(--neon-green);">Auto-Build ⚡</button>
        <button onclick="switchTab('saved')" id="tab-saved" class="tab-btn">Saved Builds</button>
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
                                <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase; margin-bottom: 5px;">Estimated Score</div>
                                <div style="color: var(--neon-blue); font-size: 12px; font-weight: bold; margin-bottom: 5px; padding: 2px 6px; border: 1px solid var(--neon-blue); display: inline-block; border-radius: 4px;">{{ $scores['tier'] }}</div>
                                <div style="font-size: 24px; font-weight: bold; color: var(--text-main); margin-bottom: 5px;">{{ number_format($scores['overall']) }} pts</div>
                                <div style="font-size: 12px; color: var(--text-muted);">
                                    Gaming: <strong style="color: #fff;">{{ number_format($scores['gaming']) }}</strong><br>
                                    Prod: <strong style="color: #fff;">{{ number_format($scores['productivity']) }}</strong>
                                </div>
                            </div>

                            <div class="score-gauge" style="position: relative; width: 80px; height: 80px;">
                                @php
                                    // SVG Math for the circular stroke offset
                                    $circumference = 2 * pi() * 34; // radius = 34
                                    $offset = $circumference - ($scores['percentage'] / 100) * $circumference;
                                @endphp
                                
                                <svg width="80" height="80" style="transform: rotate(-90deg);">
                                    <!-- Background Circle -->
                                    <circle cx="40" cy="40" r="34" stroke="#222" stroke-width="8" fill="transparent" />
                                    
                                    <!-- Foreground Animated Circle -->
                                    <circle cx="40" cy="40" r="34" stroke="var(--neon-blue)" stroke-width="8" fill="transparent"
                                            stroke-dasharray="{{ $circumference }}"
                                            stroke-dashoffset="{{ $circumference }}" 
                                            stroke-linecap="round"
                                            style="animation: fillGauge 1.5s ease-out forwards;" />
                                </svg>
                                
                                <!-- Percentage Text -->
                                <div style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; color: var(--text-main);">
                                    {{ $scores['percentage'] }}%
                                </div>
                                
                                <!-- CSS Animation for the Stroke -->
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

                    <button class="btn-primary" style="width: 100%; margin-top: 20px;">💾 Save Configuration</button>
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
                    Set your target budget and workload. Our combinatorial solver will query the database to find the highest-performing, bottleneck-free configuration within your constraints.
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

                <div class="form-group" style="text-align: center;">
                    <label class="form-label" style="font-size: 16px;">Maximum Budget (RM)</label>
                    <div id="budget-display" style="font-size: 32px; font-weight: bold; color: var(--neon-green); margin: 10px 0;">RM 6500</div>
                    <input type="range" name="budget" min="2500" max="15000" step="100" value="6500" 
                           style="width: 100%; max-width: 400px; cursor: pointer;"
                           oninput="document.getElementById('budget-display').innerText = 'RM ' + this.value">
                </div>

                <div style="text-align: center; margin-top: 30px;">
                    <button type="submit" class="btn-primary" style="font-size: 16px; padding: 12px 30px;">Generate Optimized Build</button>
                </div>
            </form>
        </div>
    </div>

    <!-- VIEW: SAVED BUILDS -->
    <div id="view-saved" class="tab-view" style="display: none;">
        <div class="card">
            <h2>Your Workspace</h2>
            <p style="color: var(--text-muted);">Please log in to view your saved configurations.</p>
        </div>
    </div>

    <!-- Simple JavaScript for Tab Switching -->
    <script>
        function switchTab(tabId) {
            // 1. Hide all views
            document.querySelectorAll('.tab-view').forEach(view => {
                view.style.display = 'none';
            });
            // 2. Remove active class from all buttons
            document.querySelectorAll('.tab-btn').forEach(btn => {
                btn.classList.remove('tab-active');
            });
            // 3. Show selected view and activate button
            document.getElementById('view-' + tabId).style.display = 'block';
            document.getElementById('tab-' + tabId).classList.add('tab-active');
        }
    </script>
</x-layout>
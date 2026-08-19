<x-layout>
    <div class="card" style="max-width: 1200px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h2 style="margin: 0;">Side-by-Side Comparison</h2>
            <a href="{{ route('builder.index') }}" class="btn-secondary" style="text-decoration: none;">&larr; Back to Workspace</a>
        </div>

        <form method="GET" action="{{ route('builder.compare') }}" id="compare-form">
            <!-- Header Row (Selectors & Overview Scores) -->
            <div style="display: grid; grid-template-columns: 160px 1fr 1fr; gap: 24px; padding: 20px 0; border-bottom: 2px solid var(--border-color); align-items: start;">
                <div style="font-weight: bold; color: var(--text-muted); text-transform: uppercase; font-size: 12px; padding-top: 40px;">
                    Matrix Overview
                </div>
                
                <!-- Build 1 Selector -->
                <div class="card" style="margin: 0; background: var(--bg-dark);">
                    <label class="form-label" style="color: var(--accent-blue);">Configuration 1</label>
                    <select name="build1" class="form-input" onchange="document.getElementById('compare-form').submit()">
                        <option value="">-- Select a Build --</option>
                        @foreach($savedBuilds as $b)
                            <option value="{{ $b->id }}" {{ request('build1') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }} (RM {{ number_format($b->total_cost, 2) }})
                            </option>
                        @endforeach
                    </select>
                    
                    @if($build1)
                        <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
                            <div style="font-size: 28px; font-weight: bold; color: var(--neon-green);">RM {{ number_format($build1->total_cost, 2) }}</div>
                            <div style="font-size: 15px; color: var(--text-main); font-weight: bold; margin-top: 5px; display: flex; align-items: center; justify-content: center;">
                                Est. Score: <span style="color: var(--neon-blue); margin-left: 5px;">{{ number_format($build1->overall_score) }} pts</span>
                                @if(isset($scores1))
                                    <div class="info-tooltip">
                                        i
                                        <div class="tooltip-content" style="width: 320px; text-align: left;">
                                            <strong style="color: var(--accent-blue); display: block; margin-bottom: 8px; font-size: 14px;">Heuristic Score Breakdown</strong>
                                            @if(isset($scores1['breakdown']['error']))
                                                {{ $scores1['breakdown']['error'] }}
                                            @else
                                                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 8px;">
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores1['breakdown']['gpu_contribution'] ?? 'N/A' }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores1['breakdown']['gpu_math'] ?? 'N/A' }}</div>
                                                    
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores1['breakdown']['cpu_contribution'] ?? 'N/A' }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores1['breakdown']['cpu_math'] ?? 'N/A' }}</div>
                                                    
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">{{ $scores1['breakdown']['ram_contribution'] ?? 'N/A' }}</div>
                                                    <div style="font-size: 10px; margin-bottom: 4px;">Formula: {{ $scores1['breakdown']['ram_math'] ?? 'N/A' }}</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px; margin-bottom: 6px; font-size: 11px;">
                                                    <div style="color: var(--text-main); font-weight: bold;">Gaming Focus:</div>
                                                    <div style="color: var(--text-muted);">(GPU × 65%) + (CPU × 25%) + (RAM × 10%)</div>
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">Productivity Focus:</div>
                                                    <div style="color: var(--text-muted);">(CPU × 50%) + (RAM × 30%) + (GPU × 20%)</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px;">
                                                    <strong style="color: {{ ($scores1['breakdown']['multiplier'] ?? 1) < 1 ? 'var(--neon-orange)' : 'var(--neon-green)' }}; display: block; margin-bottom: 2px;">
                                                        System Balance Impact
                                                    </strong>
                                                    <span style="color: var(--text-muted);">{{ $scores1['breakdown']['balance'] ?? 'N/A' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Build 2 Selector -->
                <div class="card" style="margin: 0; background: var(--bg-dark);">
                    <label class="form-label" style="color: var(--neon-orange);">Configuration 2</label>
                    <select name="build2" class="form-input" onchange="document.getElementById('compare-form').submit()">
                        <option value="">-- Select a Build --</option>
                        @foreach($savedBuilds as $b)
                            <option value="{{ $b->id }}" {{ request('build2') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }} (RM {{ number_format($b->total_cost, 2) }})
                            </option>
                        @endforeach
                    </select>
                    
                    @if($build2)
                        <div style="margin-top: 20px; text-align: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
                            <div style="font-size: 28px; font-weight: bold; color: var(--neon-green);">RM {{ number_format($build2->total_cost, 2) }}</div>
                            <div style="font-size: 15px; color: var(--text-main); font-weight: bold; margin-top: 5px; display: flex; align-items: center; justify-content: center;">
                                Est. Score: <span style="color: var(--neon-blue); margin-left: 5px;">{{ number_format($build2->overall_score) }} pts</span>
                                @if(isset($scores2))
                                    <div class="info-tooltip">
                                        i
                                        <div class="tooltip-content" style="width: 320px; text-align: left;">
                                            <strong style="color: var(--accent-blue); display: block; margin-bottom: 8px; font-size: 14px;">Score Calculation Breakdown</strong>
                                            @if(isset($scores2['breakdown']['error']))
                                                {{ $scores2['breakdown']['error'] }}
                                            @else
                                                <div style="font-size: 11px; color: var(--text-muted); margin-bottom: 8px;">
                                                    <div>• {{ $scores2['breakdown']['gpu_contribution'] ?? 'N/A' }}</div>
                                                    <div>• {{ $scores2['breakdown']['cpu_contribution'] ?? 'N/A' }}</div>
                                                    <div>• {{ $scores2['breakdown']['ram_contribution'] ?? 'N/A' }}</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px; margin-bottom: 6px; font-size: 11px;">
                                                    <div style="color: var(--text-main); font-weight: bold;">Gaming Score:</div>
                                                    <div style="color: var(--text-muted);">({{ $scores2['breakdown']['gaming'] ?? 'N/A' }})</div>
                                                    <div style="color: var(--text-main); font-weight: bold; margin-top: 4px;">Productivity Score:</div>
                                                    <div style="color: var(--text-muted);">({{ $scores2['breakdown']['formula_prod'] ?? 'N/A' }})</div>
                                                </div>

                                                <div style="border-top: 1px solid var(--border-color); padding-top: 6px;">
                                                    <strong style="color: {{ ($scores2['breakdown']['multiplier'] ?? 1) < 1 ? 'var(--neon-orange)' : 'var(--neon-green)' }}; display: block; margin-bottom: 2px;">
                                                        System Balance Impact
                                                    </strong>
                                                    <span style="color: var(--text-muted);">{{ $scores2['breakdown']['balance'] ?? 'N/A' }}</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </form>

        <!-- Component Hardware Rows with Full Specs -->
        @php
            $slots = [
                'cpu'         => ['label' => 'CPU', 'category' => 'cpu'],
                'cooler'      => ['label' => 'Cooler', 'category' => 'cooler'],
                'motherboard' => ['label' => 'Motherboard', 'category' => 'mobo'],
                'ram'         => ['label' => 'RAM', 'category' => 'ram'],
                'gpu'         => ['label' => 'Graphics Card', 'category' => 'gpu'],
                'psu'         => ['label' => 'Power Supply', 'category' => 'psu'],
                'pcCase'      => ['label' => 'PC Case', 'category' => 'case']
            ];
        @endphp

        @foreach($slots as $rel => $config)
            <div style="display: grid; grid-template-columns: 160px 1fr 1fr; gap: 24px; padding: 20px 0; border-bottom: 1px solid var(--border-color); align-items: start;">
                <div style="font-size: 13px; color: var(--text-muted); text-transform: uppercase; font-weight: bold; padding-top: 8px;">
                    {{ $config['label'] }}
                </div>
                
                <!-- Build 1 Specs Card -->
                <div style="padding: 14px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color); border-left: 4px solid var(--accent-blue);">
                    @if($build1 && $build1->$rel)
                        @php $part = $build1->$rel; @endphp
                        <strong style="font-size: 15px; display: block; color: #ffffff;">{{ $part->name }}</strong>
                        <div style="color: var(--neon-green); font-size: 14px; font-weight: 600; margin: 4px 0 10px 0;">
                            RM {{ number_format($part->price, 2) }}
                        </div>

                        <!-- Technical Specs Grid -->
                        <div class="spec-grid">
                            @if($config['category'] === 'cpu')
                                @if($part->socket)<span class="spec-badge">Socket: <strong>{{ $part->socket }}</strong></span>@endif
                                @if($part->cores)<span class="spec-badge">Cores: <strong>{{ $part->cores }}C / {{ $part->threads ?? $part->cores }}T</strong></span>@endif
                                @if($part->base_clock)<span class="spec-badge">Base: <strong>{{ $part->base_clock }} GHz</strong></span>@endif
                                @if($part->boost_clock)<span class="spec-badge">Boost: <strong>{{ $part->boost_clock }} GHz</strong></span>@endif
                                @if($part->tdp)<span class="spec-badge">TDP: <strong>{{ $part->tdp }}W</strong></span>@endif
                            @elseif($config['category'] === 'cooler')
                                @if($part->max_tdp)<span class="spec-badge">Max TDP: <strong>{{ $part->max_tdp }}W</strong></span>@endif
                                @if($part->fan_rpm)<span class="spec-badge">Fan Speed: <strong>{{ $part->fan_rpm }} RPM</strong></span>@endif
                                @if($part->noise_level)<span class="spec-badge">Noise: <strong>{{ $part->noise_level }} dB</strong></span>@endif
                            @elseif($config['category'] === 'mobo')
                                @if($part->socket)<span class="spec-badge">Socket: <strong>{{ $part->socket }}</strong></span>@endif
                                @if($part->form_factor)<span class="spec-badge">Form Factor: <strong>{{ $part->form_factor }}</strong></span>@endif
                                @if($part->ram_type)<span class="spec-badge">RAM Type: <strong>{{ $part->ram_type }}</strong></span>@endif
                                @if($part->max_ram)<span class="spec-badge">Max RAM: <strong>{{ $part->max_ram }} GB</strong></span>@endif
                                @if($part->ram_slots)<span class="spec-badge">Slots: <strong>{{ $part->ram_slots }}</strong></span>@endif
                            @elseif($config['category'] === 'ram')
                                @if($part->type)<span class="spec-badge">Type: <strong>{{ $part->type }}</strong></span>@endif
                                @if($part->speed)<span class="spec-badge">Speed: <strong>{{ $part->speed }} MHz</strong></span>@endif
                                @if($part->capacity)<span class="spec-badge">Capacity: <strong>{{ $part->capacity }} GB</strong></span>@endif
                                @if($part->cas_latency)<span class="spec-badge">CL: <strong>CL{{ $part->cas_latency }}</strong></span>@endif
                            @elseif($config['category'] === 'gpu')
                                @if($part->memory)<span class="spec-badge">VRAM: <strong>{{ $part->memory }} GB</strong></span>@endif
                                @if($part->clock_speed)<span class="spec-badge">Core Clock: <strong>{{ $part->clock_speed }} MHz</strong></span>@endif
                                @if($part->length_mm)<span class="spec-badge">Length: <strong>{{ $part->length_mm }} mm</strong></span>@endif
                                @if($part->tdp)<span class="spec-badge">TDP: <strong>{{ $part->tdp }}W</strong></span>@endif
                            @elseif($config['category'] === 'psu')
                                @if($part->wattage)<span class="spec-badge">Wattage: <strong>{{ $part->wattage }}W</strong></span>@endif
                                @if($part->efficiency_rating)<span class="spec-badge">Rating: <strong>{{ $part->efficiency_rating }}</strong></span>@endif
                                @if($part->modular)<span class="spec-badge">Modular: <strong>{{ $part->modular }}</strong></span>@endif
                            @elseif($config['category'] === 'case')
                                @if($part->form_factor)<span class="spec-badge">Form Factor: <strong>{{ $part->form_factor }}</strong></span>@endif
                                @if($part->max_gpu_length_mm)<span class="spec-badge">Max GPU: <strong>{{ $part->max_gpu_length_mm }} mm</strong></span>@endif
                            @endif
                        </div>
                    @else
                        <span style="color: #666; font-style: italic; font-size: 14px;">No component selected</span>
                    @endif
                </div>

                <!-- Build 2 Specs Card -->
                <div style="padding: 14px; background: rgba(255,255,255,0.02); border-radius: 8px; border: 1px solid var(--border-color); border-left: 4px solid var(--neon-orange);">
                    @if($build2 && $build2->$rel)
                        @php $part = $build2->$rel; @endphp
                        <strong style="font-size: 15px; display: block; color: #ffffff;">{{ $part->name }}</strong>
                        <div style="color: var(--neon-green); font-size: 14px; font-weight: 600; margin: 4px 0 10px 0;">
                            RM {{ number_format($part->price, 2) }}
                        </div>

                        <!-- Technical Specs Grid -->
                        <div class="spec-grid">
                            @if($config['category'] === 'cpu')
                                @if($part->socket)<span class="spec-badge">Socket: <strong>{{ $part->socket }}</strong></span>@endif
                                @if($part->cores)<span class="spec-badge">Cores: <strong>{{ $part->cores }}C / {{ $part->threads ?? $part->cores }}T</strong></span>@endif
                                @if($part->base_clock)<span class="spec-badge">Base: <strong>{{ $part->base_clock }} GHz</strong></span>@endif
                                @if($part->boost_clock)<span class="spec-badge">Boost: <strong>{{ $part->boost_clock }} GHz</strong></span>@endif
                                @if($part->tdp)<span class="spec-badge">TDP: <strong>{{ $part->tdp }}W</strong></span>@endif
                            @elseif($config['category'] === 'cooler')
                                @if($part->max_tdp)<span class="spec-badge">Max TDP: <strong>{{ $part->max_tdp }}W</strong></span>@endif
                                @if($part->fan_rpm)<span class="spec-badge">Fan Speed: <strong>{{ $part->fan_rpm }} RPM</strong></span>@endif
                                @if($part->noise_level)<span class="spec-badge">Noise: <strong>{{ $part->noise_level }} dB</strong></span>@endif
                            @elseif($config['category'] === 'mobo')
                                @if($part->socket)<span class="spec-badge">Socket: <strong>{{ $part->socket }}</strong></span>@endif
                                @if($part->form_factor)<span class="spec-badge">Form Factor: <strong>{{ $part->form_factor }}</strong></span>@endif
                                @if($part->ram_type)<span class="spec-badge">RAM Type: <strong>{{ $part->ram_type }}</strong></span>@endif
                                @if($part->max_ram)<span class="spec-badge">Max RAM: <strong>{{ $part->max_ram }} GB</strong></span>@endif
                                @if($part->ram_slots)<span class="spec-badge">Slots: <strong>{{ $part->ram_slots }}</strong></span>@endif
                            @elseif($config['category'] === 'ram')
                                @if($part->type)<span class="spec-badge">Type: <strong>{{ $part->type }}</strong></span>@endif
                                @if($part->speed)<span class="spec-badge">Speed: <strong>{{ $part->speed }} MHz</strong></span>@endif
                                @if($part->capacity)<span class="spec-badge">Capacity: <strong>{{ $part->capacity }} GB</strong></span>@endif
                                @if($part->cas_latency)<span class="spec-badge">CL: <strong>CL{{ $part->cas_latency }}</strong></span>@endif
                            @elseif($config['category'] === 'gpu')
                                @if($part->memory)<span class="spec-badge">VRAM: <strong>{{ $part->memory }} GB</strong></span>@endif
                                @if($part->clock_speed)<span class="spec-badge">Core Clock: <strong>{{ $part->clock_speed }} MHz</strong></span>@endif
                                @if($part->length_mm)<span class="spec-badge">Length: <strong>{{ $part->length_mm }} mm</strong></span>@endif
                                @if($part->tdp)<span class="spec-badge">TDP: <strong>{{ $part->tdp }}W</strong></span>@endif
                            @elseif($config['category'] === 'psu')
                                @if($part->wattage)<span class="spec-badge">Wattage: <strong>{{ $part->wattage }}W</strong></span>@endif
                                @if($part->efficiency_rating)<span class="spec-badge">Rating: <strong>{{ $part->efficiency_rating }}</strong></span>@endif
                                @if($part->modular)<span class="spec-badge">Modular: <strong>{{ $part->modular }}</strong></span>@endif
                            @elseif($config['category'] === 'case')
                                @if($part->form_factor)<span class="spec-badge">Form Factor: <strong>{{ $part->form_factor }}</strong></span>@endif
                                @if($part->max_gpu_length_mm)<span class="spec-badge">Max GPU: <strong>{{ $part->max_gpu_length_mm }} mm</strong></span>@endif
                            @endif
                        </div>
                    @else
                        <span style="color: #666; font-style: italic; font-size: 14px;">No component selected</span>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</x-layout>
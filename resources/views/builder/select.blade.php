<x-layout>
    <div style="max-width: 1400px; margin: 0 auto; padding: 20px;">
        
        <!-- TOP STATUS / BREADCRUMB -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <div>
                <a href="{{ route('builder.index') }}" style="color: var(--text-muted); text-decoration: none; font-size: 14px;">← Back to Workspace</a>
                <h1 style="margin: 5px 0 0 0; text-transform: uppercase; font-size: 1.8rem; color: var(--success);">
                    Select {{ $category }}
                </h1>
            </div>
            <div style="color: var(--text-muted); font-size: 14px;">
                Showing <strong style="color: var(--text-main);">{{ $parts->total() }}</strong> compatible parts
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 280px 1fr; gap: 25px; align-items: start;">
            
            <!-- ========================================================= -->
            <!-- DYNAMIC FILTER SIDEBAR -->
            <!-- ========================================================= -->
            <form id="filter-form" method="GET" action="{{ route('builder.select', $category) }}">
                <div class="card" style="background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 8px; padding: 20px;">
                    
                    <!-- Top Part List Header -->
                    <div style="background: var(--bg-main); border-radius: 6px; padding: 15px; margin-bottom: 20px; border: 1px solid var(--border-color);">
                        <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                            <span style="background: #e11d48; color: var(--text-main); padding: 2px 6px; border-radius: 4px; font-size: 12px; font-weight: bold;">⚡</span>
                            <strong style="font-size: 13px; letter-spacing: 0.5px; text-transform: uppercase;">Build Context</strong>
                        </div>
                        
                        <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; cursor: pointer; color: var(--success); font-weight: bold; margin-bottom: 15px;">
                            <input type="checkbox" name="compatibility_filter" value="1" {{ $compatibilityFilter ? 'checked' : '' }} onchange="this.form.submit()">
                            Compatibility Filter
                        </label>

                        <div style="display: flex; justify-content: space-between; border-top: 1px solid var(--border-color); padding-top: 10px; font-size: 12px;">
                            <div>
                                <span style="color: var(--text-muted); display: block;">PARTS</span>
                                <strong>{{ $selectedCount }} / 7</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block;">TOTAL</span>
                                <strong style="color: var(--success);">RM {{ number_format($totalCost, 2) }}</strong>
                            </div>
                            <div>
                                <span style="color: var(--text-muted); display: block;">EST. WATT</span>
                                <strong style="color: var(--gray-500);">{{ $estWattage }}W</strong>
                            </div>
                        </div>
                    </div>

                    <!-- Search Input -->
                    <div style="margin-bottom: 20px;">
                        <label style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 6px;">Search</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Search model or brand..." class="form-input" style="width: 100%; box-sizing: border-box; padding: 8px 12px; font-size: 13px;">
                    </div>

                    <!-- Price Filter -->
                    <div style="border-top: 1px solid var(--border-color); padding: 15px 0;">
                        <label style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 8px;">Price Range (RM)</label>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <input type="number" name="min_price" value="{{ request('min_price') }}" placeholder="Min" class="form-input" style="width: 100%; padding: 6px; font-size: 13px;">
                            <span style="color: var(--text-muted);">-</span>
                            <input type="number" name="max_price" value="{{ request('max_price') }}" placeholder="Max" class="form-input" style="width: 100%; padding: 6px; font-size: 13px;">
                        </div>
                    </div>

                    <!-- Manufacturer Checkboxes -->
                    @if(isset($filterOptions['manufacturers']) && count($filterOptions['manufacturers']) > 0)
                        <div style="border-top: 1px solid var(--border-color); padding: 15px 0;">
                            <label style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 8px;">Manufacturer</label>
                            <div style="max-height: 160px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                                @foreach($filterOptions['manufacturers'] as $mfg)
                                    <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; color: var(--text-main); cursor: pointer;">
                                        <input type="checkbox" name="manufacturers[]" value="{{ $mfg }}" {{ in_array($mfg, (array) request('manufacturers', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                                        {{ $mfg }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Socket Checkboxes (CPU & Motherboard) -->
                    @if(isset($filterOptions['sockets']) && count($filterOptions['sockets']) > 0)
                        <div style="border-top: 1px solid var(--border-color); padding: 15px 0;">
                            <label style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 8px;">Socket</label>
                            <div style="max-height: 160px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                                @foreach($filterOptions['sockets'] as $skt)
                                    <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; color: var(--text-main); cursor: pointer;">
                                        <input type="checkbox" name="sockets[]" value="{{ $skt }}" {{ in_array($skt, (array) request('sockets', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                                        {{ $skt }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- RAM Type Checkboxes (RAM & Motherboard) -->
                    @if(isset($filterOptions['ram_types']) && count($filterOptions['ram_types']) > 0)
                        <div style="border-top: 1px solid var(--border-color); padding: 15px 0;">
                            <label style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 8px;">Memory Generation</label>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                @foreach($filterOptions['ram_types'] as $rt)
                                    <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; color: var(--text-main); cursor: pointer;">
                                        <input type="checkbox" name="ram_types[]" value="{{ $rt }}" {{ in_array($rt, (array) request('ram_types', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                                        {{ $rt }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Form Factor Checkboxes (Motherboard & Case) -->
                    @if(isset($filterOptions['form_factors']) && count($filterOptions['form_factors']) > 0)
                        <div style="border-top: 1px solid var(--border-color); padding: 15px 0;">
                            <label style="font-size: 12px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; display: block; margin-bottom: 8px;">Form Factor</label>
                            <div style="display: flex; flex-direction: column; gap: 6px;">
                                @foreach($filterOptions['form_factors'] as $ff)
                                    <label style="font-size: 13px; display: flex; align-items: center; gap: 8px; color: var(--text-main); cursor: pointer;">
                                        <input type="checkbox" name="form_factors[]" value="{{ $ff }}" {{ in_array($ff, (array) request('form_factors', [])) ? 'checked' : '' }} onchange="this.form.submit()">
                                        {{ $ff }}
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Action Buttons -->
                    <div style="margin-top: 20px; display: flex; flex-direction: column; gap: 8px;">
                        <button type="submit" class="btn-primary" style="width: 100%; padding: 10px; font-size: 13px;">Apply Filters</button>
                        <a href="{{ route('builder.select', $category) }}" style="text-align: center; color: var(--text-muted); text-decoration: none; font-size: 12px; padding: 6px;">Reset Filters</a>
                    </div>
                </div>
            </form>

            <!-- ========================================================= -->
            <!-- PRODUCT RESULTS GRID -->
            <!-- ========================================================= -->
            <div>
                <!-- Admin Sync Button -->
                @auth
                    <div style="display: flex; justify-content: flex-end; margin-bottom: 15px;">
                        <form action="{{ route('admin.sync_prices') }}" method="POST" onsubmit="return confirm('This will ping 5 external servers for all components. Proceed?');">
                            @csrf
                            <button type="submit" class="btn-secondary" style="font-size: 11px; padding: 6px 12px; border-color: var(--accent); color: var(--accent);">
                                🔄 Force Live Price Sync
                            </button>
                        </form>
                    </div>
                @endauth

                @if($parts->count() > 0)
                    <div style="display: flex; flex-direction: column; gap: 12px;">
                        @foreach($parts as $part)
                            <!-- UPDATED CARD: flex-direction: column to stack info and prices -->
                            <div class="card" style="margin: 0; padding: 18px 22px; display: flex; flex-direction: column; background: var(--bg-surface); border: 1px solid var(--border-color); border-radius: 6px;">
                                
                                <!-- Top Half: Product Info & Add Button -->
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px;">
                                    <div style="flex: 1; padding-right: 20px;">
                                        <div style="font-size: 11px; text-transform: uppercase; color: var(--accent); font-weight: bold;">
                                            {{ $part->manufacturer }}
                                        </div>
                                        <h3 style="margin: 3px 0 8px 0; font-size: 1.1rem; color: var(--text-main);">
                                            {{ $part->name }}
                                        </h3>
                                        
                                        <!-- Dynamic Spec Badges per Category -->
                                        <div style="display: flex; gap: 12px; flex-wrap: wrap; font-size: 12px; color: var(--text-muted);">
                                            @if($category === 'cpu')
                                                <span><strong>Cores:</strong> {{ $part->cores }}</span>
                                                <span><strong>Socket:</strong> {{ $part->socket }}</span>
                                                <span><strong>Base Clock:</strong> {{ $part->base_clock }} GHz</span>
                                                <span><strong>TDP:</strong> {{ $part->tdp }}W</span>
                                            @elseif($category === 'gpu')
                                                <span><strong>VRAM:</strong> {{ $part->memory }} GB</span>
                                                <span><strong>Clock:</strong> {{ $part->clock_speed }} MHz</span>
                                                <span><strong>Length:</strong> {{ $part->length_mm }}mm</span>
                                                <span><strong>TDP:</strong> {{ $part->tdp }}W</span>
                                            @elseif($category === 'mobo')
                                                <span><strong>Socket:</strong> {{ $part->socket }}</span>
                                                <span><strong>RAM:</strong> {{ $part->ram_type }}</span>
                                                <span><strong>Form Factor:</strong> {{ $part->form_factor }}</span>
                                            @elseif($category === 'ram')
                                                <span><strong>Type:</strong> {{ $part->type }}</span>
                                                <span><strong>Capacity:</strong> {{ $part->capacity }} GB</span>
                                                <span><strong>Speed:</strong> {{ $part->speed }} MHz</span>
                                            @elseif($category === 'storage')
                                                <span><strong>Type:</strong> {{ $part->type }}</span>
                                                <span><strong>Capacity:</strong> {{ $part->capacity }} GB</span>
                                                <span><strong>Form Factor:</strong> {{ $part->form_factor }}</span>
                                                <span><strong>Interface:</strong> {{ $part->interface }}</span>
                                                @if($part->nvme === 'true' || $part->nvme === true)
                                                    <span><strong>NVMe:</strong> Yes</span>
                                                @endif
                                            @elseif($category === 'psu')
                                                <span><strong>Wattage:</strong> {{ $part->wattage }}W</span>
                                            @elseif($category === 'case')
                                                <span><strong>Form Factor:</strong> {{ $part->form_factor }}</span>
                                                <span><strong>Max GPU:</strong> {{ $part->max_gpu_length_mm }}mm</span>
                                            @elseif($category === 'cooler')
                                                <span><strong>Max TDP:</strong> {{ $part->max_tdp }}W</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div style="text-align: right; min-width: 140px;">
                                        <div style="font-size: 11px; color: var(--text-muted); text-transform: uppercase;">Best Market Price</div>
                                        <div style="color: var(--success); font-size: 1.3rem; font-weight: bold; margin-bottom: 8px;">
                                            RM {{ number_format($part->prices->min('price') ?? $part->price, 2) }}
                                        </div>
                                        
                                        <form action="{{ route('builder.add') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="category" value="{{ $category }}">
                                            <input type="hidden" name="id" value="{{ $part->id }}">
                                            <button type="submit" class="btn-primary" style="padding: 8px 18px; font-size: 13px; font-weight: bold; width: 100%;">
                                                + Add Part
                                            </button>
                                        </form>
                                    </div>
                                </div>

                                <!-- Bottom Half: 5 Vendor Sources -->
                                @if($part->prices && $part->prices->count() > 0)
                                    <div style="border-top: 1px solid var(--border-color); padding-top: 15px;">
                                        <div style="font-size: 11px; text-transform: uppercase; color: var(--text-muted); font-weight: bold; margin-bottom: 10px;">
                                            Live Malaysian Market Prices
                                        </div>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 10px;">
                                            @foreach($part->prices->sortBy('price') as $vendorPrice)
                                                <a href="{{ $vendorPrice->url }}" target="_blank" 
                                                style="display: flex; flex-direction: column; background: var(--bg-main); border: 1px solid {{ $vendorPrice->in_stock ? '#333' : '#4a0000' }}; padding: 10px; border-radius: 6px; text-decoration: none; transition: border-color 0.2s;">
                                                    <span style="color: var(--text-main); font-size: 12px; font-weight: bold; margin-bottom: 4px;">{{ $vendorPrice->vendor }}</span>
                                                    @if($vendorPrice->in_stock)
                                                        <span style="color: var(--success); font-size: 14px; font-weight: bold;">RM {{ number_format($vendorPrice->price, 2) }}</span>
                                                    @else
                                                        <span style="color: var(--error); font-size: 12px; font-weight: bold;">OUT OF STOCK</span>
                                                    @endif
                                                </a>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif

                            </div>
                        @endforeach
                    </div>

                    <!-- Pagination -->
                    <div style="margin-top: 25px;">
                        {{ $parts->links() }}
                    </div>
                @else
                    <div class="card" style="text-align: center; padding: 60px 20px; background: var(--bg-surface);">
                        <h3 style="color: var(--text-muted); margin-bottom: 10px;">No components match the selected filters.</h3>
                        <p style="color: var(--text-muted); font-size: 14px; margin-bottom: 20px;">
                            Try turning off the <strong>Compatibility Filter</strong> or resetting your price and brand constraints.
                        </p>
                        <a href="{{ route('builder.select', $category) }}" class="btn-secondary" style="text-decoration: none; padding: 10px 20px;">Reset All Filters</a>
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-layout>
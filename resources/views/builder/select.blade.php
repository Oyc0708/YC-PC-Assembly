<x-layout>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Select {{ strtoupper($category) }}</h2>
        <a href="{{ route('builder.index') }}" class="btn-secondary" style="text-decoration: none;">&larr; Back to Build</a>
    </div>

    <!-- Two-Column Layout for Filter & Catalog -->
    <div style="display: grid; grid-template-columns: 300px 1fr; gap: 24px; align-items: start;">
        
        <!-- FILTER SIDEBAR -->
        <div class="card" style="position: sticky; top: 20px;">
            <h3 style="margin-top: 0; margin-bottom: 20px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Filters</h3>
            
            <form method="GET" action="{{ route('builder.select', $category) }}">
                
                <!-- Search Input -->
                <div class="form-group">
                    <label class="form-label">Search Model / Brand</label>
                    <input type="text" name="search" value="{{ request('search') }}" class="form-input" placeholder="e.g. Ryzen 5...">
                </div>

                <!-- Price Range -->
                <div class="form-group">
                    <label class="form-label">Price Range (RM)</label>
                    <div style="display: flex; gap: 10px;">
                        <input type="number" name="min_price" value="{{ request('min_price') }}" class="form-input" placeholder="Min" style="width: 50%;">
                        <input type="number" name="max_price" value="{{ request('max_price') }}" class="form-input" placeholder="Max" style="width: 50%;">
                    </div>
                </div>

                <!-- Socket Filter (Only shows for CPU/Motherboard) -->
                @if(isset($sockets) && count($sockets) > 0)
                    <div class="form-group">
                        <label class="form-label">Socket Type</label>
                        <select name="socket" class="form-input">
                            <option value="">All Sockets</option>
                            @foreach($sockets as $socket)
                                <option value="{{ $socket }}" {{ request('socket') == $socket ? 'selected' : '' }}>
                                    {{ $socket }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- RAM Generation Filter (Only shows for RAM/Motherboard) -->
                @if(isset($ramTypes) && count($ramTypes) > 0)
                    <div class="form-group">
                        <label class="form-label">Memory Generation</label>
                        <select name="ram_type" class="form-input">
                            <option value="">All Types</option>
                            @foreach($ramTypes as $type)
                                <option value="{{ $type }}" {{ request('ram_type') == $type ? 'selected' : '' }}>
                                    {{ $type }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div style="display: flex; gap: 10px; margin-top: 30px;">
                    <a href="{{ route('builder.select', $category) }}" class="btn-secondary" style="flex: 1; text-decoration: none; text-align: center; padding-top: 10px;">Clear</a>
                    <button type="submit" class="btn-primary" style="flex: 2;">Apply Filters</button>
                </div>
            </form>
        </div>

        <!-- CATALOG GRID -->
        <div>
            @if($parts->isEmpty())
                <div class="card" style="text-align: center; padding: 40px; color: var(--text-muted);">
                    <h3>No components found</h3>
                    <p>Try adjusting your filters or clearing your search.</p>
                </div>
            @else
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
                    @foreach($parts as $part)
                        <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between; transition: transform 0.2s ease, border-color 0.2s ease;">
                            <div>
                                <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">{{ $part->manufacturer }}</div>
                                <h4 style="margin: 5px 0 15px 0; font-size: 16px;">{{ $part->name }}</h4>
                                
                                <!-- Dynamic Specs List -->
                                <div style="font-size: 13px; color: var(--text-muted); margin-bottom: 15px; display: grid; gap: 4px;">
                                    @if(isset($part->socket)) <div>Socket: <span style="color: var(--text-main);">{{ $part->socket }}</span></div> @endif
                                    @if(isset($part->tdp)) <div>TDP: <span style="color: var(--text-main);">{{ $part->tdp }}W</span></div> @endif
                                    @if(isset($part->ram_type)) <div>RAM: <span style="color: var(--text-main);">{{ $part->ram_type }}</span></div> @endif
                                    @if(isset($part->type)) <div>Type: <span style="color: var(--text-main);">{{ $part->type }}</span></div> @endif
                                    @if(isset($part->length_mm)) <div>Length: <span style="color: var(--text-main);">{{ $part->length_mm }}mm</span></div> @endif
                                    @if(isset($part->wattage)) <div>Wattage: <span style="color: var(--text-main);">{{ $part->wattage }}W</span></div> @endif
                                </div>
                            </div>
                            
                            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
                                <span style="color: var(--neon-green); font-weight: bold; font-size: 18px;">RM {{ number_format($part->price, 2) }}</span>
                                
                                <form action="{{ route('builder.add') }}" method="POST" style="margin: 0;">
                                    @csrf
                                    <input type="hidden" name="category" value="{{ $category }}">
                                    <input type="hidden" name="id" value="{{ $part->id }}">
                                    <button type="submit" class="btn-primary" style="padding: 6px 12px; font-size: 13px;">Add to Build</button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination Links -->
                <div style="margin-top: 30px;">
                    {{ $parts->links() }}
                </div>
            @endif
        </div>
    </div>
</x-layout>
<x-layout>
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2>Select {{ strtoupper($category) }}</h2>
        <a href="{{ route('builder.index') }}" class="btn-secondary" style="text-decoration: none;">&larr; Back to Build</a>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
        @foreach($parts as $part)
            <div class="card" style="margin-bottom: 0; display: flex; flex-direction: column; justify-content: space-between;">
                <div>
                    <div style="font-size: 12px; color: var(--text-muted); text-transform: uppercase;">{{ $part->manufacturer }}</div>
                    <h4 style="margin: 5px 0 15px 0;">{{ $part->name }}</h4>
                </div>
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid var(--border-color); padding-top: 15px;">
                    <span style="color: var(--neon-green); font-weight: bold; font-size: 18px;">RM {{ number_format($part->price, 2) }}</span>
                    
                    <form action="{{ route('builder.add') }}" method="POST" style="margin: 0;">
                        @csrf
                        <input type="hidden" name="category" value="{{ $category }}">
                        <input type="hidden" name="id" value="{{ $part->id }}">
                        <button type="submit" class="btn-primary">Add to Build</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Pagination Links -->
    <div style="margin-top: 30px;">
        {{ $parts->links() }}
    </div>
</x-layout>
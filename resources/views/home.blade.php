<x-layout>
    <div style="padding: 2rem 0;">
        
        <!-- Hero Section -->
        <div class="card text-center mb-6" style="padding: 5rem 1rem; border-radius: 12px; border: 1px solid var(--accent); box-shadow: 0 0 30px rgba(0, 240, 255, 0.1);">
            <h1 style="font-size: 3rem; margin-bottom: 1.25rem; font-weight: 800; letter-spacing: 2px;">
                Forge Your Perfect PC
            </h1>
            
            <p style="font-size: 1.125rem; color: var(--text-muted); max-width: 650px; margin: 0 auto 2.5rem auto; line-height: 1.6;">
                Take the guesswork out of PC building. Our intelligent engine analyzes raw hardware specifications to guarantee physical compatibility, calculate thermal limits, and auto-generate the optimal rig for your budget.
            </p>
            
            <div class="flex justify-center gap-4 flex-wrap">
                <a href="{{ route('builder.index') }}" class="btn btn-primary" style="padding: 0.875rem 2rem; font-size: 1rem;">
                    Get Started
                </a>
                
                @guest
                    <a href="{{ route('login') }}" class="btn btn-secondary" style="padding: 0.875rem 2rem; font-size: 1rem;">
                        Login to Save Builds
                    </a>
                @endguest
            </div>
        </div>

        <!-- Features Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 1.5rem;">
            
            <!-- Feature 1 -->
            <div class="card text-center" style="padding: 2.5rem 1.5rem;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">⚡</div>
                <h3 class="mb-2" style="font-size: 1.25rem;">Smart Auto-Builder</h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.6;">
                    Set your budget and workload. Our algorithm allocates funds dynamically and safely selects top-tier components based on real hardware metrics.
                </p>
            </div>
            
            <!-- Feature 2 -->
            <div class="card text-center" style="padding: 2.5rem 1.5rem;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">🛡️</div>
                <h3 class="mb-2" style="font-size: 1.25rem;">Strict Compatibility</h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.6;">
                    From socket alignments and RAM generations to physical GPU clearances and strict PSU wattage overheads, we ensure every part fits perfectly.
                </p>
            </div>
            
            <!-- Feature 3 -->
            <div class="card text-center" style="padding: 2.5rem 1.5rem;">
                <div style="font-size: 2rem; margin-bottom: 1rem;">📊</div>
                <h3 class="mb-2" style="font-size: 1.25rem;">Heuristic Scoring</h3>
                <p style="color: var(--text-muted); font-size: 0.875rem; line-height: 1.6;">
                    Compare different setups with our custom validation engine that intelligently evaluates core counts, clock speeds, memory capacities, and bottlenecks.
                </p>
            </div>
            
        </div>
        
    </div>
</x-layout>
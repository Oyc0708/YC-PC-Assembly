<x-layout>
    <div style="max-width: 1200px; margin: 0 auto; padding: 40px 20px;">
        
        <!-- Hero Section -->
        <div class="card" style="text-align: center; padding: 80px 20px; border-radius: 12px; margin-bottom: 60px; background: rgba(0,0,0,0.02);">
            <h1 style="font-size: 3rem; margin-bottom: 20px; color: var(--neon-green, #4ade80); font-weight: 800; letter-spacing: -1px;">
                Forge Your Perfect PC
            </h1>
            
            <p style="font-size: 1.2rem; color: var(--text-muted, #9ca3af); max-width: 650px; margin: 0 auto 40px auto; line-height: 1.6;">
                Take the guesswork out of PC building. Our intelligent engine analyzes raw hardware specifications to guarantee physical compatibility, calculate thermal limits, and auto-generate the optimal rig for your budget.
            </p>
            
            <div style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap;">
                <a href="{{ route('builder.index') }}" class="btn-primary" style="padding: 15px 40px; font-size: 1.1rem; text-decoration: none; font-weight: bold; border-radius: 6px;">
                    Get Started
                </a>
                
                @guest
                    <a href="{{ route('login') }}" style="padding: 15px 40px; font-size: 1.1rem; text-decoration: none; color: var(--text-muted, #9ca3af); border: 2px solid var(--text-muted, #9ca3af); border-radius: 6px; font-weight: bold; transition: 0.3s; opacity: 0.7;">
                        Login to Save Builds
                    </a>
                @endguest
            </div>
        </div>

        <!-- Features Section -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px;">
            
            <!-- Feature 1 -->
            <div class="card" style="text-align: center; padding: 40px 30px;">
                <div style="font-size: 2.5rem; margin-bottom: 20px;">⚡</div>
                <h3 style="margin-bottom: 15px; font-size: 1.3rem;">Smart Auto-Builder</h3>
                <p style="color: var(--text-muted, #9ca3af); font-size: 0.95rem; line-height: 1.6;">
                    Set your budget and workload. Our algorithm allocates funds dynamically and safely selects top-tier components based on real hardware metrics.
                </p>
            </div>
            
            <!-- Feature 2 -->
            <div class="card" style="text-align: center; padding: 40px 30px;">
                <div style="font-size: 2.5rem; margin-bottom: 20px;">🛡️</div>
                <h3 style="margin-bottom: 15px; font-size: 1.3rem;">Strict Compatibility</h3>
                <p style="color: var(--text-muted, #9ca3af); font-size: 0.95rem; line-height: 1.6;">
                    From socket alignments and RAM generations to physical GPU clearances and strict PSU wattage overheads, we ensure every part fits perfectly.
                </p>
            </div>
            
            <!-- Feature 3 -->
            <div class="card" style="text-align: center; padding: 40px 30px;">
                <div style="font-size: 2.5rem; margin-bottom: 20px;">📊</div>
                <h3 style="margin-bottom: 15px; font-size: 1.3rem;">Heuristic Scoring</h3>
                <p style="color: var(--text-muted, #9ca3af); font-size: 0.95rem; line-height: 1.6;">
                    Compare different setups with our custom validation engine that intelligently evaluates core counts, clock speeds, memory capacities, and bottlenecks.
                </p>
            </div>
            
        </div>
        
    </div>
</x-layout>
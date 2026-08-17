<x-layout>
    <div style="max-width: 450px; margin: 40px auto;">
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 30px;">Welcome Back</h2>

            <!-- Session Status -->
            @if (session('status'))
                <div style="color: var(--neon-green); margin-bottom: 20px; font-weight: bold;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="form-input">
                    @error('email')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input id="password" type="password" name="password" required class="form-input">
                    @error('password')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <label style="display: flex; align-items: center; color: var(--text-muted); font-size: 14px;">
                        <input type="checkbox" name="remember" style="margin-right: 8px;">
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="color: var(--accent-blue); font-size: 14px; text-decoration: none;">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px;">Log in</button>
            </form>
        </div>
    </div>
</x-layout>
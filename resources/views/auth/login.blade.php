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

            <!-- Divider -->
            <div style="display: flex; align-items: center; margin: 25px 0;">
                <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--text-muted); opacity: 0.3; margin: 0;">
                <span style="padding: 0 15px; color: var(--text-muted); font-size: 14px; font-weight: bold; text-transform: uppercase;">Or</span>
                <hr style="flex-grow: 1; border: none; border-top: 1px solid var(--text-muted); opacity: 0.3; margin: 0;">
            </div>

            <!-- Continue as Guest Button -->
            <div style="text-align: center;">
                <a href="{{ route('builder.index') }}"
                style="display: inline-block; width: 100%; padding: 12px; background-color: transparent; border: 1px solid var(--text-muted); color: var(--text-muted); text-decoration: none; border-radius: 4px; font-weight: bold; box-sizing: border-box; transition: 0.3s; text-align: center;">
                    Continue as Guest
                </a>
            </div>
            
            <!-- Optional: Link to Register -->
            <div style="text-align: center; margin-top: 20px;">
                <span style="color: var(--text-muted); font-size: 14px;">Don't have an account? </span>
                <a href="{{ route('register') }}" style="color: var(--accent-blue); font-size: 14px; text-decoration: none; font-weight: bold;">Sign up</a>
            </div>

        </div>
    </div>
</x-layout>
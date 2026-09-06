<x-layout>
    <div class="auth-container">
        <div class="auth-card card">
            <h2 class="auth-title auth-header">Welcome Back</h2>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4" style="color: var(--success); font-weight: 500;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input @error('email') input-error @enderror">
                    @error('email')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password">Password</label>
                    <input id="password" type="password" name="password" required class="input @error('password') input-error @enderror">
                    @error('password')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex justify-between items-center mb-6">
                    <label style="display: flex; align-items: center; cursor: pointer; margin-bottom: 0;">
                        <input type="checkbox" name="remember" style="margin-right: 8px;">
                        Remember me
                    </label>
                    @if (Route::has('password.request'))
                        <a href="{{ route('password.request') }}" style="font-size: 0.875rem;">Forgot password?</a>
                    @endif
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Log in</button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-6" style="margin: 1.5rem 0;">
                <hr style="flex: 1; border: none; border-top: 1px solid var(--border-color);">
                <span style="padding: 0 1rem; color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase;">Or</span>
                <hr style="flex: 1; border: none; border-top: 1px solid var(--border-color);">
            </div>

            <!-- Continue as Guest Button -->
            <div class="text-center">
                <a href="{{ route('builder.index') }}" class="btn btn-secondary" style="width: 100%;">
                    Continue as Guest
                </a>
            </div>
            
            <!-- Link to Register -->
            <div class="text-center mt-6">
                <span style="color: var(--text-muted); font-size: 0.875rem;">Don't have an account? </span>
                <a href="{{ route('register') }}" style="font-weight: 500; font-size: 0.875rem;">Sign up</a>
            </div>
        </div>
    </div>
</x-layout>
<x-layout>
    <div class="auth-container">
        <div class="auth-card card">
            <h2 class="auth-title auth-header">Forgot Password</h2>

            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 1.5rem;">
                Forgot your password? No problem. Just let us know your email address and we will email you a password reset link that will allow you to choose a new one.
            </p>

            <!-- Session Status -->
            @if (session('status'))
                <div class="mb-4" style="color: var(--success); font-weight: 500;">
                    {{ session('status') }}
                </div>
            @endif

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus class="input @error('email') input-error @enderror">
                    @error('email')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary" style="width: 100%;">Email Password Reset Link</button>
            </form>

            <!-- Link back to Login -->
            <div class="text-center mt-6">
                <a href="{{ route('login') }}" style="color: var(--text-muted); font-size: 0.875rem;">Remember your password? <span style="font-weight: 500; color: var(--accent);">Log in</span></a>
            </div>
        </div>
    </div>
</x-layout>

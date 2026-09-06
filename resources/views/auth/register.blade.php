<x-layout>
    <div class="auth-container">
        <div class="auth-card card">
            <h2 class="auth-title auth-header">Create an Account</h2>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="form-group">
                    <label for="name">Username</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="input @error('name') input-error @enderror">
                    @error('name')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required class="input @error('email') input-error @enderror">
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

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="input @error('password_confirmation') input-error @enderror">
                    @error('password_confirmation')
                        <span class="error-msg">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn btn-primary mt-4" style="width: 100%;">Register</button>
                
                <div class="text-center mt-6">
                    <a href="{{ route('login') }}" style="color: var(--text-muted); font-size: 0.875rem;">Already registered? <span style="font-weight: 500; color: var(--accent);">Log in</span></a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
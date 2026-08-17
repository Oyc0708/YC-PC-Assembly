<x-layout>
    <div style="max-width: 450px; margin: 40px auto;">
        <div class="card">
            <h2 style="text-align: center; margin-bottom: 30px;">Create an Account</h2>

            <form method="POST" action="{{ route('register') }}">
                @csrf

                <!-- Name -->
                <div class="form-group">
                    <label for="name" class="form-label">Username</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus class="form-input">
                    @error('name')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <!-- Email Address -->
                <div class="form-group">
                    <label for="email" class="form-label">Email Address</label>
                    <input id="email" type="email" name="email" value="{{ old('email') }}" required class="form-input">
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

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password_confirmation" class="form-label">Confirm Password</label>
                    <input id="password_confirmation" type="password" name="password_confirmation" required class="form-input">
                    @error('password_confirmation')
                        <span class="text-danger">{{ $message }}</span>
                    @enderror
                </div>

                <button type="submit" class="btn-primary" style="width: 100%; padding: 12px; margin-top: 10px;">Register</button>
                
                <div style="text-align: center; margin-top: 20px;">
                    <a href="{{ route('login') }}" style="color: var(--text-muted); font-size: 14px; text-decoration: none;">Already registered? <span style="color: var(--accent-blue);">Log in</span></a>
                </div>
            </form>
        </div>
    </div>
</x-layout>
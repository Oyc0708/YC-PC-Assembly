<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>YC's PC Building Platform</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <div>
        <h1 style="margin: 0; color: var(--neon-blue);">YC's PC Building Platform</h1>
        <p style="margin: 5px 0 0 0; color: var(--text-muted);">Laravel Compatibility Engine</p>
    </div>
    <div class="header-actions" style="display: flex; gap: 10px; align-items: center;">
        @auth
            <!-- Logged In State -->
            <div style="display: flex; align-items: center; gap: 15px; background: var(--surface); padding: 5px 15px; border-radius: 20px; border: 1px solid var(--border-color);">
                <span style="color: var(--text-main); font-weight: bold;">👤 {{ Auth::user()->name }}</span>
                <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                    @csrf
                    <button type="submit" class="btn-secondary" style="border: none; color: var(--neon-red); cursor: pointer; padding: 5px;">Logout</button>
                </form>
            </div>
        @else
            <!-- Guest State -->
            <a href="{{ route('login') }}" class="btn-secondary" style="text-decoration: none;">Login</a>
            <a href="{{ route('register') }}" class="btn-primary" style="text-decoration: none;">Sign Up</a>
        @endauth
    </div>
</header>

        <!-- Main Content Injected Here -->
        <main>
            {{ $slot }}
        </main>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', "YC's PC Building Platform") }}</title>
    @php
        $manifestPath = public_path('build/manifest.json');
        if (file_exists($manifestPath)) {
            $manifest = json_decode(file_get_contents($manifestPath), true);
            if (isset($manifest['resources/css/app.css'])) {
                echo '<link rel="stylesheet" href="'.asset('build/'.$manifest['resources/css/app.css']['file']).'">';
            }
            if (isset($manifest['resources/js/app.js'])) {
                echo '<script type="module" src="'.asset('build/'.$manifest['resources/js/app.js']['file']).'"></script>';
            }
        }
    @endphp
</head>
<body>
    <div class="app-container">
        <!-- Header -->
        <header class="site-header">
            <div class="container header-inner">
                <div>
                    <a href="{{ route('home') }}" class="site-title">YC's PC Building <span>Platform</span></a>
                    <p class="mt-1" style="color: var(--text-muted); font-size: 0.875rem;">Laravel Compatibility Engine</p>
                </div>
                <nav class="nav-links">
                    <a href="{{ route('builder.index') }}" class="nav-link">Builder</a>
                    @auth
                        <div class="flex items-center gap-4">
                            <span style="color: var(--text-main); font-weight: 500;">{{ Auth::user()->name }}</span>
                            <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
                                @csrf
                                <button type="submit" class="btn btn-secondary" style="padding: 0.4rem 0.8rem;">Logout</button>
                            </form>
                        </div>
                    @else
                        <a href="{{ route('login') }}" class="btn btn-secondary">Login</a>
                        <a href="{{ route('register') }}" class="btn btn-primary">Sign Up</a>
                    @endauth
                </nav>
            </div>
        </header>

        <!-- Main Content Injected Here -->
        <main class="main-content">
            <div class="container">
                {{ $slot }}
            </div>
        </main>
    </div>
</body>
</html>
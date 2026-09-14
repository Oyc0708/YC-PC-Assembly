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

    <style>
        /* Laravel Pagination UI Fix for Custom Theme */
        nav[role="navigation"] svg {
            width: 1.25rem;
            height: 1.25rem;
        }
        nav[role="navigation"] .hidden.sm\:flex-1.sm\:flex.sm\:items-center.sm\:justify-between {
            display: flex;
            align-items: center;
            justify-content: space-between;
            width: 100%;
            margin-top: 20px;
        }
        nav[role="navigation"] p {
            color: var(--text-muted);
            font-size: 0.875rem;
            margin: 0;
        }
        nav[role="navigation"] .relative.z-0.inline-flex {
            display: inline-flex;
            gap: 4px;
        }
        nav[role="navigation"] a, 
        nav[role="navigation"] span[aria-disabled] span,
        nav[role="navigation"] span[aria-current] span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 14px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            color: var(--text-main);
            text-decoration: none;
            border-radius: 6px;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }
        nav[role="navigation"] a:hover {
            background: var(--bg-main);
            border-color: var(--accent);
            color: var(--accent);
        }
        nav[role="navigation"] span[aria-current="page"] span {
            background: var(--accent);
            color: #000;
            border-color: var(--accent);
            font-weight: bold;
        }
        nav[role="navigation"] span[aria-disabled="true"] span {
            opacity: 0.5;
            cursor: not-allowed;
        }
        /* Hide mobile paginator since desktop one is forced flex */
        nav[role="navigation"] .flex.justify-between.flex-1.sm\:hidden {
            display: none;
        }
    </style>
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
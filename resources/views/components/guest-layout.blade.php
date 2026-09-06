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
        <!-- Main Content -->
        <main class="main-content" style="display: flex; justify-content: center; align-items: center; min-height: 100vh;">
            {{ $slot }}
        </main>
    </div>
</body>
</html>

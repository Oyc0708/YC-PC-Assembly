<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BuilderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
// Fixed Collision: Moved the welcome logic here and made this the dedicated home route.
Route::get('/', function () {
    if (!session()->has('has_been_welcomed')) {
        session(['has_been_welcomed' => true]);
        
        $name = auth()->check() ? auth()->user()->name : 'User';
        session()->flash('welcome_toast', "Welcome back, {$name}!");
    }

    return view('home'); // Ensure you have a home.blade.php template
})->name('home');

/*
|--------------------------------------------------------------------------
| PC Builder Routes
|--------------------------------------------------------------------------
*/
// Grouping builder routes prevents collisions and keeps the URL structure clean
Route::prefix('builder')->group(function () {
    
    // Public Builder Routes
    Route::get('/', [BuilderController::class, 'index'])->name('builder.index');
    Route::get('/select/{category}', [BuilderController::class, 'selectCategory'])->name('builder.select');
    Route::post('/add-part', [BuilderController::class, 'addPart'])->name('builder.add');
    Route::post('/remove-part', [BuilderController::class, 'removePart'])->name('builder.remove');
    Route::post('/auto-build', [BuilderController::class, 'generateAutoBuild'])->name('builder.autobuild');
    Route::post('/clear', [BuilderController::class, 'clearBuild'])->name('builder.clear');

    // Authenticated Builder Routes
    Route::middleware('auth')->group(function () {
        Route::post('/save', [BuilderController::class, 'saveBuild'])->name('builder.save');
        Route::post('/load/{id}', [BuilderController::class, 'loadBuild'])->name('builder.load');
        Route::get('/compare', [BuilderController::class, 'compareView'])->name('builder.compare');
        Route::post('/delete/{id}', [BuilderController::class, 'deleteBuild'])->name('builder.delete');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Routes
|--------------------------------------------------------------------------
*/
// Fixed DoS Vulnerability: Added aggressive rate limiting (1 request per 5 minutes)
// and an explicit check for admin privileges.
Route::post('/admin/force-price-update', function (Request $request) {
    
    // SECURITY: Ensure user is logged in AND matches your specific email.
    // Replace 'your-email@example.com' with your actual account email.
    if (!auth()->check() || auth()->user()->email !== 'your-email@example.com') {
        abort(403, 'Unauthorized action. Administrator privileges required.');
    }

    // Programmatically run the fetcher command
    Artisan::call('prices:fetch');

    return back()->with('success', 'Market prices from all 5 Malaysian vendors have been synced successfully!');
})->middleware(['auth', 'throttle:1,5'])->name('admin.sync_prices');

/*
|--------------------------------------------------------------------------
| Laravel Breeze Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    return redirect()->route('builder.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
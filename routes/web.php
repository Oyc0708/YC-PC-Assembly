<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\BuilderController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| PC Builder Routes
|--------------------------------------------------------------------------
*/
// Set the root URL back to your Builder Controller
Route::get('/', [BuilderController::class, 'index'])->name('builder.index');
Route::get('/select/{category}', [BuilderController::class, 'selectCategory'])->name('builder.select');
Route::post('/add-part', [BuilderController::class, 'addPart'])->name('builder.add');
Route::post('/remove-part', [BuilderController::class, 'removePart'])->name('builder.remove');


/*
|--------------------------------------------------------------------------
| Laravel Breeze Authentication Routes
|--------------------------------------------------------------------------
*/
Route::get('/dashboard', function () {
    // You can redirect this back to the builder, or keep it as a user profile page
    return redirect('/');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/auto-build', [BuilderController::class, 'generateAutoBuild'])->name('builder.autobuild');

Route::post('/builder/save', [App\Http\Controllers\BuilderController::class, 'saveBuild'])
    ->name('builder.save')
    ->middleware('auth');

Route::post('/builder/load/{id}', [App\Http\Controllers\BuilderController::class, 'loadBuild'])
->name('builder.load')
->middleware('auth');

Route::get('/builder/compare', [App\Http\Controllers\BuilderController::class, 'compareView'])
    ->name('builder.compare')
    ->middleware('auth');

Route::post('/builder/delete/{id}', [App\Http\Controllers\BuilderController::class, 'deleteBuild'])
    ->name('builder.delete')
    ->middleware('auth');

Route::get('/builder', [BuilderController::class, 'index'])->name('builder.index');

Route::get('/', function () {
    // Check if we have already welcomed the user during this session
    if (!session()->has('has_been_welcomed')) {
        session(['has_been_welcomed' => true]);
        
        // Dynamically grab the user's name or default to 'User'
        $name = auth()->check() ? auth()->user()->name : 'User';
        session()->flash('welcome_toast', "Welcome back, {$name}!");
    }

    return view('home');
})->name('home');

Route::post('/admin/force-price-update', function (Request $request) {
    // SECURITY: Ensure only authenticated users/admins can trigger this
    if (!auth()->check()) {
        abort(403, 'Unauthorized action.');
    }

    // Programmatically run the fetcher command
    Artisan::call('prices:fetch');

    return back()->with('success', 'Market prices from all 5 Malaysian vendors have been synced successfully!');
})->name('admin.sync_prices');

Route::post('/builder/clear', [App\Http\Controllers\BuilderController::class, 'clearBuild'])->name('builder.clear');

require __DIR__.'/auth.php';
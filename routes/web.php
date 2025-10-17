<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return view('campaign');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Email Agent Campaign Routes
    Route::get('/campaign', function () {
        return view('campaign');
    })->name('campaign');
    
    // API Routes for Email Agent (using web authentication)
    Route::prefix('api')->group(function () {
        // Campaign routes with rate limiting
        Route::middleware(['throttle:5,1'])->group(function () {
            Route::post('/campaigns', [App\Http\Controllers\CampaignController::class, 'store']);
            Route::post('/senders', [App\Http\Controllers\CampaignController::class, 'createSender']);
        });

        // Status and sender listing routes (less restrictive)
        Route::middleware(['throttle:30,1'])->group(function () {
            Route::get('/campaigns/{id}/status', [App\Http\Controllers\CampaignController::class, 'status']);
            Route::get('/senders', [App\Http\Controllers\CampaignController::class, 'senders']);
            Route::get('/dashboard', [App\Http\Controllers\CampaignController::class, 'dashboard']);
        });
    });
});

require __DIR__.'/auth.php';

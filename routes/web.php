<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    return redirect()->route('campaign');
})->middleware(['auth', 'verified'])->name('dashboard');

// Unsubscribe routes (public)
Route::get('/unsubscribe', [App\Http\Controllers\UnsubscribeController::class, 'index'])->name('unsubscribe');
Route::post('/unsubscribe', [App\Http\Controllers\UnsubscribeController::class, 'unsubscribe'])->name('unsubscribe.process');
Route::get('/unsubscribe/success', [App\Http\Controllers\UnsubscribeController::class, 'success'])->name('unsubscribe.success');

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
        Route::middleware(['throttle:60,1'])->group(function () {
            Route::post('/campaigns', [App\Http\Controllers\CampaignController::class, 'store']);
            Route::post('/senders', [App\Http\Controllers\CampaignController::class, 'createSender']);
        });

        // Status and sender listing routes (less restrictive)
        Route::middleware(['throttle:30,1'])->group(function () {
            Route::get('/campaigns/{id}/status', [App\Http\Controllers\CampaignController::class, 'status']);
            Route::get('/campaigns-history', [App\Http\Controllers\CampaignController::class, 'campaignsHistory']);
            Route::get('/senders', [App\Http\Controllers\CampaignController::class, 'senders']);
            Route::get('/dashboard', [App\Http\Controllers\CampaignController::class, 'dashboard']);
            
            // Email Templates routes
            Route::get('/email-templates', [App\Http\Controllers\EmailTemplateController::class, 'index']);
            Route::get('/email-templates/active', [App\Http\Controllers\EmailTemplateController::class, 'active']);
            Route::get('/email-templates/{id}', [App\Http\Controllers\EmailTemplateController::class, 'show']);
            Route::post('/email-templates', [App\Http\Controllers\EmailTemplateController::class, 'store'])->middleware(['throttle:10,1']);
            
            // SMTP Configuration routes
            Route::get('/smtp-configurations', [App\Http\Controllers\SmtpConfigurationController::class, 'index']);
            Route::get('/smtp-configurations/active', [App\Http\Controllers\SmtpConfigurationController::class, 'active']);
            Route::get('/smtp-configurations/default', [App\Http\Controllers\SmtpConfigurationController::class, 'default']);
            Route::get('/smtp-configurations/emails', [App\Http\Controllers\SmtpConfigurationController::class, 'emails']);
            Route::post('/smtp-configurations/{id}/set-default', [App\Http\Controllers\SmtpConfigurationController::class, 'setDefault']);
            Route::post('/smtp-configurations/{id}/toggle-active', [App\Http\Controllers\SmtpConfigurationController::class, 'toggleActive']);
            Route::post('/smtp-configurations/{id}/test', [App\Http\Controllers\SmtpConfigurationController::class, 'test']);
            
            // Campaign control routes
            Route::post('/campaigns/{id}/pause', [App\Http\Controllers\CampaignController::class, 'pause']);
            Route::post('/campaigns/{id}/resume', [App\Http\Controllers\CampaignController::class, 'resume']);
            Route::delete('/campaigns/{id}', [App\Http\Controllers\CampaignController::class, 'destroy']);
            Route::post('/campaigns/restart-stuck', [App\Http\Controllers\CampaignController::class, 'restartStuckCampaigns']);
            
            // Blacklist routes
            Route::get('/blacklist', [App\Http\Controllers\BlacklistController::class, 'index']);
            Route::post('/blacklist/email', [App\Http\Controllers\BlacklistController::class, 'addEmail']);
            Route::post('/blacklist/domain', [App\Http\Controllers\BlacklistController::class, 'addDomain']);
            Route::post('/blacklist/bulk', [App\Http\Controllers\BlacklistController::class, 'bulkAdd']);
            Route::get('/blacklist/check/{email}', [App\Http\Controllers\BlacklistController::class, 'check']);
            Route::delete('/blacklist/{id}', [App\Http\Controllers\BlacklistController::class, 'destroy']);
        });
        
        // SMTP Configuration management routes (more restrictive)
        Route::middleware(['throttle:10,1'])->group(function () {
            Route::post('/smtp-configurations', [App\Http\Controllers\SmtpConfigurationController::class, 'store']);
            Route::put('/smtp-configurations/{id}', [App\Http\Controllers\SmtpConfigurationController::class, 'update']);
            Route::delete('/smtp-configurations/{id}', [App\Http\Controllers\SmtpConfigurationController::class, 'destroy']);
        });
    });
});

require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\CampaignController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Protected API routes - require web authentication
Route::middleware(['web', 'auth'])->group(function () {
    // Campaign routes with rate limiting
    Route::middleware(['throttle:5,1'])->group(function () {
        Route::post('/campaigns', [CampaignController::class, 'store']);
        Route::post('/senders', [CampaignController::class, 'createSender']);
    });

    // Status and sender listing routes (less restrictive)
    Route::middleware(['throttle:30,1'])->group(function () {
        Route::get('/campaigns/{id}/status', [CampaignController::class, 'status']);
        Route::get('/senders', [CampaignController::class, 'senders']);
        Route::get('/dashboard', [CampaignController::class, 'dashboard']);
    });
});

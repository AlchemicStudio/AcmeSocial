<?php

use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DonationController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/campaigns', [CampaignController::class, 'index']);
    Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);

    Route::post('/campaigns/{campaign}/donations', [DonationController::class, 'storeCampaignDonation']);
    Route::get('/campaigns/{campaign}/donations', [DonationController::class, 'campaignDonations']);
    Route::get('/campaigns/{campaign}/donations/{donation}', [DonationController::class, 'campaignDonation']);

    Route::get('/campaigns/{campaign}/statistics', [CampaignController::class, 'statistics']);
    Route::put('/campaigns/{campaign}/approve', [CampaignController::class, 'approve']);
    Route::put('/campaigns/{campaign}/reject', [CampaignController::class, 'reject']);

    Route::post('/campaigns/{campaign}/logo', [CampaignController::class, 'uploadLogo']);
    Route::get('/campaigns/{campaign}/logo', [CampaignController::class, 'getLogo']);
    Route::put('/campaigns/{campaign}/logo', [CampaignController::class, 'updateLogo']);
    Route::delete('/campaigns/{campaign}/logo', [CampaignController::class, 'deleteLogo']);

    Route::post('/campaigns/{campaign}/media', [CampaignController::class, 'uploadMedia']);
    Route::get('/campaigns/{campaign}/media', [CampaignController::class, 'listMedia']);
    Route::get('/campaigns/{campaign}/media/{media}', [CampaignController::class, 'getMedia']);
    Route::put('/campaigns/{campaign}/media/{media}', [CampaignController::class, 'updateMedia']);
    Route::delete('/campaigns/{campaign}/media/{media}', [CampaignController::class, 'deleteMedia']);

    Route::apiResource('donations', DonationController::class);

    Route::get('/transactions', [TransactionController::class, 'index']);
});

Route::middleware(['auth:sanctum', 'admin'])->group(function () {
    Route::apiResource('users', UserController::class);
    Route::get('/users/search', [UserController::class, 'search']);

    // User permissions management
    Route::get('/users/{user}/permissions', [UserController::class, 'getUserPermissions']);
    Route::post('/users/{user}/permissions', [UserController::class, 'assignPermissions']);
    Route::put('/users/{user}/permissions', [UserController::class, 'syncPermissions']);
    Route::delete('/users/{user}/permissions', [UserController::class, 'removePermissions']);

    Route::get('/permissions', [PermissionController::class, 'index']);
});

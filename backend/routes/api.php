<?php

use App\Http\Controllers\Api\BuyerRequestController;
use App\Http\Controllers\Api\FarmerListingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\AuthController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/test-auth', function () {
    try {
        $user = auth('api')->user();
        return response()->json(['user' => $user, 'success' => true]);
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage(), 'success' => false]);
    }
})->middleware('auth:api');

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
Route::get('/activate/{token}', [AuthController::class, 'activate']);

Route::apiResource('products', ProductController::class)->middleware('auth:api');
Route::apiResource('farmer-listings', FarmerListingController::class)->middleware('auth:api');
Route::apiResource('buyer-requests', BuyerRequestController::class)->middleware('auth:api');

// Analytics routes
Route::middleware('auth:api')->group(function () {
    Route::get('/farmer/analytics', [AnalyticsController::class, 'farmerAnalytics']);
    Route::get('/buyer/analytics', [AnalyticsController::class, 'buyerAnalytics']);
});

// Admin routes
Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'adminDashboard']);
    Route::get('/deals', [AnalyticsController::class, 'adminDeals']);
});
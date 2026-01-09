<?php

use App\Http\Controllers\Api\BuyerRequestController;
use App\Http\Controllers\Api\FarmerListingController;
use App\Http\Controllers\Api\ProductController;
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
Route::post('/verify', [AuthController::class, 'verify'])->middleware('auth:api');

Route::apiResource('products', ProductController::class)->middleware('auth:api');
Route::apiResource('farmer-listings', FarmerListingController::class)->middleware('auth:api');
Route::apiResource('buyer-requests', BuyerRequestController::class)->middleware('auth:api');
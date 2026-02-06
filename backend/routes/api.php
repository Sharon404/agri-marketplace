<?php

use App\Http\Controllers\Api\BuyerRequestController;
use App\Http\Controllers\Api\FarmerListingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\DealsController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\ReviewsController;
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

Route::apiResource('products', ProductController::class);
Route::apiResource('farmer-listings', FarmerListingController::class);
Route::apiResource('buyer-requests', BuyerRequestController::class);

// Analytics routes
Route::middleware('auth:api')->group(function () {
    Route::get('/farmer/analytics', [AnalyticsController::class, 'farmerAnalytics']);
    Route::get('/buyer/analytics', [AnalyticsController::class, 'buyerAnalytics']);
});

// Deals routes
Route::middleware('auth:api')->prefix('deals')->group(function () {
    Route::get('/', [DealsController::class, 'index']);
    Route::get('/statistics', [DealsController::class, 'statistics']);
    Route::get('/{id}', [DealsController::class, 'show']);
    Route::post('/from-listing', [DealsController::class, 'createFromListing']);
    Route::post('/from-request', [DealsController::class, 'createFromRequest']);
    Route::patch('/{id}/status', [DealsController::class, 'updateStatus']);
    Route::patch('/{id}/payment', [DealsController::class, 'updatePaymentStatus']);
});

// Messages routes
Route::middleware('auth:api')->prefix('messages')->group(function () {
    Route::get('/conversations', [MessagesController::class, 'conversations']);
    Route::get('/conversations/{conversationId}', [MessagesController::class, 'getMessages']);
    Route::post('/send', [MessagesController::class, 'sendMessage']);
    Route::patch('/conversations/{conversationId}/read', [MessagesController::class, 'markAsRead']);
    Route::get('/unread-count', [MessagesController::class, 'unreadCount']);
});

// Reviews routes
Route::middleware('auth:api')->prefix('reviews')->group(function () {
    Route::get('/user/{userId}', [ReviewsController::class, 'index']);
    Route::get('/user/{userId}/statistics', [ReviewsController::class, 'statistics']);
    Route::post('/', [ReviewsController::class, 'store']);
    Route::patch('/{id}', [ReviewsController::class, 'update']);
    Route::delete('/{id}', [ReviewsController::class, 'destroy']);
});

// Admin routes
Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'adminDashboard']);
    Route::get('/deals', [AnalyticsController::class, 'adminDeals']);
});
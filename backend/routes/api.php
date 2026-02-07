<?php

use App\Http\Controllers\Api\BuyerRequestController;
use App\Http\Controllers\Api\FarmerListingController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\DealsController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\ReviewsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\EmailVerificationController;
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

// Authentication routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:api');
Route::post('/refresh', [AuthController::class, 'refresh'])->middleware('auth:api');
Route::get('/activate/{token}', [AuthController::class, 'activate']);

// Password Reset routes
Route::post('/password/forgot', [PasswordResetController::class, 'forgotPassword']);
Route::post('/password/reset', [PasswordResetController::class, 'resetPassword']);
Route::post('/password/change', [PasswordResetController::class, 'changePassword'])->middleware('auth:api');

// Email Verification routes
Route::middleware('auth:api')->prefix('email')->group(function () {
    Route::post('/send-verification', [EmailVerificationController::class, 'sendVerification']);
    Route::post('/verify', [EmailVerificationController::class, 'verifyCode']);
    Route::post('/resend', [EmailVerificationController::class, 'resendCode']);
});

Route::apiResource('products', ProductController::class);

// Farmer listings and buyer requests require email verification for write operations
Route::middleware(['auth:api', 'require.email.verified'])->group(function () {
    Route::post('farmer-listings', [FarmerListingController::class, 'store']);
    Route::patch('farmer-listings/{id}', [FarmerListingController::class, 'update']);
    Route::delete('farmer-listings/{id}', [FarmerListingController::class, 'destroy']);
    
    Route::post('buyer-requests', [BuyerRequestController::class, 'store']);
    Route::patch('buyer-requests/{id}', [BuyerRequestController::class, 'update']);
    Route::delete('buyer-requests/{id}', [BuyerRequestController::class, 'destroy']);
});

// Listings and requests read operations don't require verification
Route::get('farmer-listings', [FarmerListingController::class, 'index']);
Route::get('farmer-listings/{id}', [FarmerListingController::class, 'show']);
Route::get('buyer-requests', [BuyerRequestController::class, 'index']);
Route::get('buyer-requests/{id}', [BuyerRequestController::class, 'show']);

// Analytics routes
Route::middleware('auth:api')->group(function () {
    Route::get('/farmer/analytics', [AnalyticsController::class, 'farmerAnalytics']);
    Route::get('/buyer/analytics', [AnalyticsController::class, 'buyerAnalytics']);
});

// Deals routes - require email verification
Route::middleware(['auth:api', 'require.email.verified'])->prefix('deals')->group(function () {
    Route::get('/', [DealsController::class, 'index']);
    Route::get('/statistics', [DealsController::class, 'statistics']);
    Route::get('/{id}', [DealsController::class, 'show']);
    Route::post('/from-listing', [DealsController::class, 'createFromListing']);
    Route::post('/from-request', [DealsController::class, 'createFromRequest']);
    Route::patch('/{id}/status', [DealsController::class, 'updateStatus']);
    Route::patch('/{id}/payment', [DealsController::class, 'updatePaymentStatus']);
});

// Messages routes - require email verification
Route::middleware(['auth:api', 'require.email.verified'])->prefix('messages')->group(function () {
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
    Route::post('/', [ReviewsController::class, 'store'])->middleware('require.email.verified');
    Route::patch('/{id}', [ReviewsController::class, 'update'])->middleware('require.email.verified');
    Route::delete('/{id}', [ReviewsController::class, 'destroy'])->middleware('require.email.verified');
});

// Admin routes
Route::middleware('auth:api')->prefix('admin')->group(function () {
    Route::get('/dashboard', [AnalyticsController::class, 'adminDashboard']);
    Route::get('/deals', [AnalyticsController::class, 'adminDeals']);
});
<?php

use App\Http\Controllers\Api\BuyerRequestController;
use App\Http\Controllers\Api\FarmerListingController;
use App\Http\Controllers\Api\FarmerSupplyController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\AnalyticsController;
use App\Http\Controllers\Api\DealsController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\ReviewsController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Admin\DealController as AdminDealController;
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

// User routes - mode switching without breaking existing API
Route::middleware('auth:api')->prefix('user')->group(function () {
    Route::get('/capabilities', [UserController::class, 'getCapabilities']);
});

// Capability request route
Route::middleware('auth:api')->prefix('capabilities')->group(function () {
    Route::post('/request', [UserController::class, 'requestCapability']);
});

// Products - public read
Route::apiResource('products', ProductController::class, ['only' => ['index', 'show']]);

// Farmer Supplies - new managed marketplace model
// Farmers submit availability, admin matches with requests
Route::middleware(['auth:api', 'require.email.verified'])->prefix('supplies')->group(function () {
    Route::get('/', [FarmerSupplyController::class, 'index']); // My supplies (farmer only)
    Route::post('/', [FarmerSupplyController::class, 'store']); // Create supply (farmer only)
    Route::get('/{id}', [FarmerSupplyController::class, 'show']); // View supply
    Route::patch('/{id}', [FarmerSupplyController::class, 'update']); // Update supply (farmer only)
    Route::delete('/{id}', [FarmerSupplyController::class, 'destroy']); // Delete supply (farmer only)
});

// Public view of available supplies (for buyers)
Route::get('/supplies/available', [FarmerSupplyController::class, 'listAvailable']);

// Farmer listings - deprecated in favor of farmer-supplies but kept for backward compatibility
Route::middleware(['auth:api', 'require.email.verified'])->group(function () {
    Route::post('farmer-listings', [FarmerListingController::class, 'store']);
    Route::patch('farmer-listings/{id}', [FarmerListingController::class, 'update']);
    Route::delete('farmer-listings/{id}', [FarmerListingController::class, 'destroy']);
    
    Route::post('buyer-requests', [BuyerRequestController::class, 'store']);
    Route::patch('buyer-requests/{id}', [BuyerRequestController::class, 'update']);
    Route::delete('buyer-requests/{id}', [BuyerRequestController::class, 'destroy']);
});

Route::get('farmer-listings', [FarmerListingController::class, 'index']);
Route::get('farmer-listings/{id}', [FarmerListingController::class, 'show']);
Route::get('buyer-requests', [BuyerRequestController::class, 'index']);
Route::get('buyer-requests/{id}', [BuyerRequestController::class, 'show']);

// Analytics routes
Route::middleware('auth:api')->group(function () {
    Route::get('/farmer/analytics', [AnalyticsController::class, 'farmerAnalytics']);
    Route::get('/buyer/analytics', [AnalyticsController::class, 'buyerAnalytics']);
});

// Deals routes (READ ONLY for farmers/buyers) - require email verification
// Farmers and buyers can ONLY view and accept/reject deals
// Deals are created by admin only
Route::middleware(['auth:api', 'require.email.verified'])->prefix('deals')->group(function () {
    Route::get('/', [DealsController::class, 'index']); // View my deals
    Route::get('/{id}', [DealsController::class, 'show']); // View deal details
    Route::patch('/{id}/accept', [DealsController::class, 'accept']); // Buyer/farmer accepts
    Route::patch('/{id}/reject', [DealsController::class, 'reject']); // Buyer/farmer rejects
    Route::get('/statistics', [DealsController::class, 'statistics']); // Deal statistics
    // REMOVED: createFromListing, createFromRequest (peer-to-peer creation disabled)
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

// Admin routes - MANAGED MARKETPLACE
// Only admins can create and modify deals
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    // Dashboard
    Route::get('/dashboard', [AnalyticsController::class, 'adminDashboard']);
    
    // Deal Management (admin only)
    Route::get('/deals', [AdminDealController::class, 'listDeals']);
    Route::post('/deals', [AdminDealController::class, 'createDeal']); // ADMIN CREATES DEALS
    Route::get('/deals/{id}', [AdminDealController::class, 'showDeal']);
    Route::patch('/deals/{id}', [AdminDealController::class, 'updateDeal']);
    Route::patch('/deals/{id}/cancel', [AdminDealController::class, 'cancelDeal']);
    Route::patch('/deals/{id}/release-escrow', [AdminDealController::class, 'releaseEscrow']);
    
    // Buyer Requests (view for matching)
    Route::get('/buyer-requests', [AdminDealController::class, 'listBuyerRequests']);
    
    // Farmer Supplies (view for matching)
    Route::get('/farmer-supplies', [AdminDealController::class, 'listFarmerSupplies']);
});
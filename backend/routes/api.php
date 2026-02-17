<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SellerController;
use Illuminate\Support\Facades\Route;

Route::post('/register', [AuthController::class, 'register']);
Route::post('/register/seller', [AuthController::class, 'registerSeller']);
Route::post('/login', [AuthController::class, 'login']);

Route::get('/products', [ProductController::class, 'index']);
Route::get('/products/{product}', [ProductController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Admin-only: create other admins
    Route::post('/admin/create', [AuthController::class, 'createAdmin']);

    // Admin-only: seller verification endpoints
    Route::get('/admin/sellers/pending', [AdminController::class, 'pendingSellers']);
    Route::get('/admin/sellers', [AdminController::class, 'allSellers']);
    Route::patch('/admin/sellers/{seller}/verify', [AdminController::class, 'verifySeller']);
    Route::patch('/admin/sellers/{seller}/reject', [AdminController::class, 'rejectSeller']);

    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{product}', [ProductController::class, 'update']);
    Route::delete('/products/{product}', [ProductController::class, 'destroy']);

    Route::get('/cart', [CartController::class, 'show']);
    Route::post('/cart/items', [CartController::class, 'add']);
    Route::put('/cart/items/{cartItem}', [CartController::class, 'update']);
    Route::delete('/cart/items/{cartItem}', [CartController::class, 'remove']);
    Route::delete('/cart', [CartController::class, 'clear']);

    Route::post('/checkout', [CheckoutController::class, 'store']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::get('/orders/{order}', [OrderController::class, 'show']);
    Route::patch('/orders/{order}/status', [OrderController::class, 'updateStatus']);

    Route::get('/seller/profile', [SellerController::class, 'profile']);
    Route::patch('/seller/profile', [SellerController::class, 'updateProfile']);
    Route::get('/seller/orders', [SellerController::class, 'orders']);
});

<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Admin Dashboard Routes (Web-based)
Route::middleware(['auth:web', 'admin'])->prefix('admin-dashboard')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('index');
    
    // User approval routes
    Route::get('/users/pending', [AdminDashboardController::class, 'pendingUsers'])->name('users.pending');
    Route::get('/users/approved', [AdminDashboardController::class, 'approvedUsers'])->name('users.approved');
    Route::get('/users/rejected', [AdminDashboardController::class, 'rejectedUsers'])->name('users.rejected');
    Route::post('/users/{user}/approve', [AdminDashboardController::class, 'approveUser'])->name('users.approve');
    Route::post('/users/{user}/reject', [AdminDashboardController::class, 'rejectUser'])->name('users.reject');
    
    // Deal management routes
    Route::get('/deals', [AdminDashboardController::class, 'deals'])->name('deals.index');
    Route::post('/deals', [AdminDashboardController::class, 'createDeal'])->name('deals.create');
    Route::patch('/deals/{deal}/status', [AdminDashboardController::class, 'updateDealStatus'])->name('deals.update');
    
    // Analytics routes
    Route::get('/analytics', [AdminDashboardController::class, 'analytics'])->name('analytics');
    Route::get('/analytics/data', [AdminDashboardController::class, 'analyticsData'])->name('analytics.data');
});


<?php

use App\Http\Controllers\Admin\CapabilityController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\UserApprovalController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // User management
    Route::get('/users', [DashboardController::class, 'users']);
    Route::post('/users/{user}/verify', [DashboardController::class, 'verifyUser']);

    // User approval workflow
    Route::prefix('approvals')->group(function () {
        Route::get('/pending', [UserApprovalController::class, 'pendingUsers']);
        Route::get('/approved', [UserApprovalController::class, 'approvedUsers']);
        Route::get('/rejected', [UserApprovalController::class, 'rejectedUsers']);
        Route::get('/statistics', [UserApprovalController::class, 'statistics']);
        Route::post('/users/{user}/approve', [UserApprovalController::class, 'approve']);
        Route::post('/users/{user}/reject', [UserApprovalController::class, 'reject']);
    });

    // Capability approval workflow
    Route::prefix('capabilities')->group(function () {
        Route::get('/', [CapabilityController::class, 'index']);
        Route::post('/users/{user}/approve-buy', [CapabilityController::class, 'approveBuy']);
        Route::post('/users/{user}/approve-sell', [CapabilityController::class, 'approveSell']);
        Route::post('/users/{user}/reject-buy', [CapabilityController::class, 'rejectBuy']);
        Route::post('/users/{user}/reject-sell', [CapabilityController::class, 'rejectSell']);
    });

    // Deal management
    Route::get('/deals', [DashboardController::class, 'deals']);
    Route::post('/deals', [DashboardController::class, 'createDeal']);
    Route::patch('/deals/{deal}/status', [DashboardController::class, 'updateDealStatus']);

    // Transaction management
    Route::get('/transactions', [DashboardController::class, 'transactions']);
    Route::post('/transactions/{transaction}/release', [DashboardController::class, 'releaseFunds']);
    Route::post('/transactions/{transaction}/refund', [DashboardController::class, 'refundFunds']);

    // Logistics management
    Route::get('/logistics', [DashboardController::class, 'logisticsJobs']);
    Route::post('/logistics/{job}/assign', [DashboardController::class, 'assignLogistics']);
    Route::post('/logistics/{job}/complete', [DashboardController::class, 'completeDelivery']);

    // Disputes
    Route::get('/disputes', [DashboardController::class, 'disputes']);
    Route::post('/disputes/{dispute}/resolve', [DashboardController::class, 'resolveDispute']);

    // Audit logs
    Route::get('/audit-logs', [DashboardController::class, 'auditLogs']);
});
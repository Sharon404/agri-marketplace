@extends('admin.layout')

@section('title', 'Dashboard')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-4"><i class="bi bi-speedometer2"></i> Admin Dashboard</h2>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['pending_approvals'] ?? 0 }}</div>
                <div class="stat-label">Pending Approvals</div>
                <a href="{{ route('admin.users.pending') }}" class="btn btn-sm btn-primary mt-3">Review</a>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['approved_users'] ?? 0 }}</div>
                <div class="stat-label">Approved Users</div>
                <small class="text-muted">Active on platform</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total_farmers'] ?? 0 }}</div>
                <div class="stat-label">Total Farmers</div>
                <small class="text-muted">Supply side</small>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['total_buyers'] ?? 0 }}</div>
                <div class="stat-label">Total Buyers</div>
                <small class="text-muted">Demand side</small>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['active_listings'] ?? 0 }}</div>
                <div class="stat-label">Active Listings</div>
                <small class="text-muted">Farmer listings available</small>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="stat-card">
                <div class="stat-value">{{ $stats['active_requests'] ?? 0 }}</div>
                <div class="stat-label">Active Buyer Requests</div>
                <small class="text-muted">Purchase requests pending</small>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-hourglass-bottom"></i> Quick Actions
                </div>
                <div class="card-body">
                    <a href="{{ route('admin.users.pending') }}" class="btn btn-outline-primary btn-sm mb-2 w-100">
                        <i class="bi bi-hourglass-bottom"></i> Review Pending Users ({{ $stats['pending_approvals'] ?? 0 }})
                    </a>
                    <a href="{{ route('admin.deals.index') }}" class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-handshake"></i> Manage Deals
                    </a>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-info-circle"></i> System Status
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Platform Status:</span>
                        <span class="badge bg-success">Active</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Database:</span>
                        <span class="badge bg-success">Connected</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span>API Status:</span>
                        <span class="badge bg-success">Running</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

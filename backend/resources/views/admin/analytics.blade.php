@extends('admin.layout')

@section('title', 'Analytics')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><i class="bi bi-bar-chart"></i> Platform Analytics</h2>
        </div>
    </div>
    
    <div class="row" id="analyticsCards">
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value" id="total-users">0</div>
                <div class="stat-label">Total Users</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value" id="approved-users">0</div>
                <div class="stat-label">Approved Users</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value" id="pending-users">0</div>
                <div class="stat-label">Pending Approvals</div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="stat-card">
                <div class="stat-value" id="active-deals">0</div>
                <div class="stat-label">Active Deals</div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-people"></i> User Breakdown
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="stat-value text-success" id="total-farmers">0</div>
                                <small class="text-muted">Farmers</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="stat-value text-primary" id="total-buyers">0</div>
                                <small class="text-muted">Buyers</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <i class="bi bi-graph-up"></i> Platform Activity
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-6">
                            <div class="text-center">
                                <div class="stat-value" id="total-listings">0</div>
                                <small class="text-muted">Active Listings</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="text-center">
                                <div class="stat-value" id="total-requests">0</div>
                                <small class="text-muted">Active Requests</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    fetch('{{ route("admin.analytics.data") }}')
        .then(response => response.json())
        .then(data => {
            document.getElementById('total-users').textContent = data.total_users || 0;
            document.getElementById('approved-users').textContent = data.approved_users || 0;
            document.getElementById('pending-users').textContent = data.pending_users || 0;
            document.getElementById('active-deals').textContent = data.deals || 0;
            document.getElementById('total-farmers').textContent = data.farmers || 0;
            document.getElementById('total-buyers').textContent = data.buyers || 0;
            document.getElementById('total-listings').textContent = data.listings || 0;
            document.getElementById('total-requests').textContent = data.requests || 0;
        })
        .catch(error => console.error('Error loading analytics:', error));
});
</script>
@endsection

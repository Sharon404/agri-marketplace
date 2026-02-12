@extends('admin.layout')

@section('title', 'Capability Management')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="mb-0">
                <i class="bi bi-shield-check"></i> Capability Approval System
            </h1>
            <p class="text-muted">Manage user buy/sell capabilities</p>
        </div>
        <div class="col-md-6 text-end">
            <span class="badge bg-info">{{ $capabilities->total() }} Total Requests</span>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">Filter by Type</label>
                    <select class="form-select" id="typeFilter">
                        <option value="">All Types</option>
                        <option value="buy">Buy Capability</option>
                        <option value="sell">Sell Capability</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Filter by Status</label>
                    <select class="form-select" id="statusFilter">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">&nbsp;</label>
                    <button class="btn btn-primary w-100" id="filterBtn">
                        <i class="bi bi-funnel"></i> Apply Filters
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Capabilities Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>User Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Buy Request</th>
                        <th>Sell Request</th>
                        <th>Status</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($capabilities as $cap)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm flex-shrink-0 me-2">
                                        <div class="avatar-title rounded-circle bg-light text-primary">
                                            {{ substr($cap->user->name, 0, 1) }}
                                        </div>
                                    </div>
                                    <div>
                                        <h6 class="mb-0">{{ $cap->user->name }}</h6>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <a href="mailto:{{ $cap->user->email }}">{{ $cap->user->email }}</a>
                            </td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($cap->user->role) }}</span>
                            </td>
                            <td>
                                @if($cap->buy_requested_at)
                                    <div class="mb-2">
                                        <span class="badge bg-warning">Requested</span>
                                    </div>
                                    <small class="text-muted">{{ $cap->buy_requested_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cap->sell_requested_at)
                                    <div class="mb-2">
                                        <span class="badge bg-warning">Requested</span>
                                    </div>
                                    <small class="text-muted">{{ $cap->sell_requested_at->diffForHumans() }}</small>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @if($cap->buy_approved_at || $cap->sell_approved_at)
                                    <span class="badge bg-success">
                                        <i class="bi bi-check-circle"></i> Approved
                                    </span>
                                @elseif($cap->status === 'rejected')
                                    <span class="badge bg-danger">
                                        <i class="bi bi-x-circle"></i> Rejected
                                    </span>
                                @else
                                    <span class="badge bg-secondary">
                                        <i class="bi bi-clock"></i> Pending
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="dropdown text-center">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        @if($cap->buy_requested_at && !$cap->buy_approved_at)
                                            <li>
                                                <a class="dropdown-item approve-btn" href="#" data-user-id="{{ $cap->user->id }}" data-type="buy">
                                                    <i class="bi bi-check-lg text-success"></i> Approve Buy
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item reject-btn" href="#" data-user-id="{{ $cap->user->id }}" data-type="buy">
                                                    <i class="bi bi-x-lg text-danger"></i> Reject Buy
                                                </a>
                                            </li>
                                            <li><hr class="dropdown-divider"></li>
                                        @endif

                                        @if($cap->sell_requested_at && !$cap->sell_approved_at)
                                            <li>
                                                <a class="dropdown-item approve-btn" href="#" data-user-id="{{ $cap->user->id }}" data-type="sell">
                                                    <i class="bi bi-check-lg text-success"></i> Approve Sell
                                                </a>
                                            </li>
                                            <li>
                                                <a class="dropdown-item reject-btn" href="#" data-user-id="{{ $cap->user->id }}" data-type="sell">
                                                    <i class="bi bi-x-lg text-danger"></i> Reject Sell
                                                </a>
                                            </li>
                                        @endif

                                        @if(!($cap->buy_requested_at || $cap->sell_requested_at))
                                            <li>
                                                <span class="dropdown-item text-muted">No pending requests</span>
                                            </li>
                                        @endif
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                                    <p>No capability requests found</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    @if($capabilities->hasPages())
        <div class="d-flex justify-content-center mt-4">
            {{ $capabilities->links('pagination::bootstrap-5') }}
        </div>
    @endif
</div>

<!-- Approval Modal -->
<div class="modal fade" id="approvalModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bi bi-check-circle"></i> Approve Capability
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Approve <strong id="capType"></strong> capability for <strong id="userName"></strong>?</p>
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i>
                    <small>This action will enable the user to <span id="capAction"></span> on the platform.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmApproveBtn">
                    <i class="bi bi-check-lg"></i> Approve
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-x-circle"></i> Reject Capability
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Reject <strong id="rejectCapType"></strong> capability request for <strong id="rejectUserName"></strong>?</p>

                <div class="form-group">
                    <label class="form-label">Reason (Optional)</label>
                    <textarea class="form-control" id="rejectionReason" rows="3" placeholder="Enter reason for rejection..."></textarea>
                </div>

                <div class="alert alert-warning mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    <small>The user will be notified about the rejection.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRejectBtn">
                    <i class="bi bi-x-lg"></i> Reject
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div class="position-fixed bottom-0 end-0 p-3" style="z-index: 1050;">
    <div id="statusToast" class="toast" role="alert">
        <div class="toast-header">
            <strong class="me-auto">
                <i id="toastIcon" class="bi"></i>
                <span id="toastTitle"></span>
            </strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body" id="toastMessage"></div>
    </div>
</div>

@push('scripts')
<script>
    const modal = new bootstrap.Modal(document.getElementById('approvalModal'), {});
    const rejectModal = new bootstrap.Modal(document.getElementById('rejectModal'), {});
    const toastElement = document.getElementById('statusToast');
    const toast = new bootstrap.Toast(toastElement);

    let currentUserId = null;
    let currentType = null;

    // Filter logic
    document.getElementById('filterBtn').addEventListener('click', function() {
        const type = document.getElementById('typeFilter').value;
        const status = document.getElementById('statusFilter').value;
        
        let url = '{{ route("admin.capabilities.index") }}';
        const params = new URLSearchParams();
        
        if (type) params.append('type', type);
        if (status) params.append('status', status);
        
        if (params.toString()) {
            url += '?' + params.toString();
        }
        
        window.location.href = url;
    });

    // Approve button
    document.querySelectorAll('.approve-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentUserId = this.dataset.userId;
            currentType = this.dataset.type;
            
            const userName = this.closest('tr').querySelector('h6').textContent;
            document.getElementById('userName').textContent = userName;
            document.getElementById('capType').textContent = currentType;
            document.getElementById('capAction').textContent = currentType === 'buy' ? 'buy products' : 'sell products';
            
            modal.show();
        });
    });

    // Reject button
    document.querySelectorAll('.reject-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentUserId = this.dataset.userId;
            currentType = this.dataset.type;
            
            const userName = this.closest('tr').querySelector('h6').textContent;
            document.getElementById('rejectUserName').textContent = userName;
            document.getElementById('rejectCapType').textContent = currentType;
            document.getElementById('rejectionReason').value = '';
            
            rejectModal.show();
        });
    });

    // Confirm approval
    document.getElementById('confirmApproveBtn').addEventListener('click', function() {
        const url = `/api/admin/capabilities/users/${currentUserId}/approve-${currentType}`;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            }
        })
        .then(response => response.json())
        .then(data => {
            modal.hide();
            showToast(data.success ? 'success' : 'danger', 
                      data.success ? 'Success' : 'Error', 
                      data.message);
            
            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            modal.hide();
            showToast('danger', 'Error', 'Failed to process approval: ' + error.message);
        });
    });

    // Confirm rejection
    document.getElementById('confirmRejectBtn').addEventListener('click', function() {
        const reason = document.getElementById('rejectionReason').value;
        const url = `/api/admin/capabilities/users/${currentUserId}/reject-${currentType}`;
        
        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Bearer ${localStorage.getItem('token')}`,
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ reason: reason })
        })
        .then(response => response.json())
        .then(data => {
            rejectModal.hide();
            showToast(data.success ? 'success' : 'danger', 
                      data.success ? 'Success' : 'Error', 
                      data.message);
            
            if (data.success) {
                setTimeout(() => location.reload(), 1500);
            }
        })
        .catch(error => {
            rejectModal.hide();
            showToast('danger', 'Error', 'Failed to process rejection: ' + error.message);
        });
    });

    // Helper function to show toast
    function showToast(type, title, message) {
        const toastTitle = document.getElementById('toastTitle');
        const toastMessage = document.getElementById('toastMessage');
        const toastIcon = document.getElementById('toastIcon');
        
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        
        toastElement.className = `toast alert alert-${type}`;
        
        if (type === 'success') {
            toastIcon.className = 'bi bi-check-circle-fill';
        } else if (type === 'danger') {
            toastIcon.className = 'bi bi-exclamation-circle-fill';
        }
        
        toast.show();
    }
</script>
@endpush

@push('styles')
<style>
    .avatar-sm {
        width: 2rem;
        height: 2rem;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .avatar-title {
        width: 100%;
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 0.875rem;
    }

    .table-hover tbody tr:hover {
        background-color: #f8f9fa;
    }

    .btn-light:hover {
        background-color: #e2e6ea;
    }

    .modal-header.bg-success,
    .modal-header.bg-danger {
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .alert {
        border-left: 4px solid;
    }

    .alert-info {
        border-left-color: #0dcaf0;
        background-color: #cfe2ff;
    }

    .alert-warning {
        border-left-color: #ffc107;
        background-color: #fff3cd;
    }
</style>
@endpush

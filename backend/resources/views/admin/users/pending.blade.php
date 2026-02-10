@extends('admin.layout')

@section('title', 'Pending User Approvals')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-hourglass-bottom"></i> Pending User Approvals</h2>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="{{ route('admin.users.pending') }}" class="btn btn-sm btn-primary active">All</a>
                <a href="{{ route('admin.users.pending', ['role' => 'farmer']) }}" class="btn btn-sm btn-outline-primary">Farmers</a>
                <a href="{{ route('admin.users.pending', ['role' => 'buyer']) }}" class="btn btn-sm btn-outline-primary">Buyers</a>
            </div>
        </div>
    </div>
    
    @if ($users->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Role</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-info">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $user->created_at->format('M d, Y') }}
                                </small>
                            </td>
                            <td>
                                <form action="{{ route('admin.users.approve', $user) }}" method="POST" style="display: inline;">
                                    @csrf
                                    <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Approve this user?')">
                                        <i class="bi bi-check-circle"></i> Approve
                                    </button>
                                </form>
                                <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#rejectModal{{ $user->id }}">
                                    <i class="bi bi-x-circle"></i> Reject
                                </button>
                                
                                <!-- Reject Modal -->
                                <div class="modal fade" id="rejectModal{{ $user->id }}" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Reject User</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form action="{{ route('admin.users.reject', $user) }}" method="POST">
                                                @csrf
                                                <div class="modal-body">
                                                    <div class="mb-3">
                                                        <label for="reason{{ $user->id }}" class="form-label">Reason for Rejection</label>
                                                        <textarea class="form-control" id="reason{{ $user->id }}" name="reason" rows="4" required></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-danger">Reject</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                {{ $users->links() }}
            </ul>
        </nav>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No pending users for approval.
        </div>
    @endif
</div>
@endsection

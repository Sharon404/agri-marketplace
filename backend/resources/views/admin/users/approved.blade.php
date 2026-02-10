@extends('admin.layout')

@section('title', 'Approved Users')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-check-circle"></i> Approved Users</h2>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="{{ route('admin.users.approved') }}" class="btn btn-sm btn-primary active">All</a>
                <a href="{{ route('admin.users.approved', ['role' => 'farmer']) }}" class="btn btn-sm btn-outline-primary">Farmers</a>
                <a href="{{ route('admin.users.approved', ['role' => 'buyer']) }}" class="btn btn-sm btn-outline-primary">Buyers</a>
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
                        <th>Approved</th>
                        <th>Approved By</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-success">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $user->approved_at?->format('M d, Y H:i') ?? 'N/A' }}
                                </small>
                            </td>
                            <td>
                                {{ $user->approvedByAdmin?->name ?? 'N/A' }}
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
            <i class="bi bi-info-circle"></i> No approved users yet.
        </div>
    @endif
</div>
@endsection

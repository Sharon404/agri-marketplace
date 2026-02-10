@extends('admin.layout')

@section('title', 'Rejected Users')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-x-circle"></i> Rejected Users</h2>
        </div>
        <div class="col-md-6 text-end">
            <div class="btn-group">
                <a href="{{ route('admin.users.rejected') }}" class="btn btn-sm btn-primary active">All</a>
                <a href="{{ route('admin.users.rejected', ['role' => 'farmer']) }}" class="btn btn-sm btn-outline-primary">Farmers</a>
                <a href="{{ route('admin.users.rejected', ['role' => 'buyer']) }}" class="btn btn-sm btn-outline-primary">Buyers</a>
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
                        <th>Reason</th>
                        <th>Rejected</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                        <tr>
                            <td><strong>{{ $user->name }}</strong></td>
                            <td>{{ $user->email }}</td>
                            <td>{{ $user->phone ?? 'N/A' }}</td>
                            <td>
                                <span class="badge bg-danger">{{ ucfirst($user->role) }}</span>
                            </td>
                            <td>
                                <small>{{ $user->rejection_reason ?? 'No reason provided' }}</small>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $user->approved_at?->format('M d, Y') ?? 'N/A' }}
                                </small>
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
            <i class="bi bi-info-circle"></i> No rejected users.
        </div>
    @endif
</div>
@endsection

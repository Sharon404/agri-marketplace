@extends('admin.layout')

@section('title', 'Manage Deals')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-6">
            <h2><i class="bi bi-handshake"></i> Manage Deals</h2>
        </div>
        <div class="col-md-6 text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createDealModal">
                <i class="bi bi-plus-circle"></i> Create Deal
            </button>
        </div>
    </div>
    
    <!-- Status Filter -->
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="btn-group" role="group">
                <a href="{{ route('admin.deals.index') }}" class="btn btn-sm btn-outline-primary">All</a>
                <a href="{{ route('admin.deals.index', ['status' => 'pending']) }}" class="btn btn-sm btn-outline-primary">Pending</a>
                <a href="{{ route('admin.deals.index', ['status' => 'accepted']) }}" class="btn btn-sm btn-outline-primary">Accepted</a>
                <a href="{{ route('admin.deals.index', ['status' => 'completed']) }}" class="btn btn-sm btn-outline-primary">Completed</a>
            </div>
        </div>
    </div>
    
    @if ($deals->count() > 0)
        <div class="table-responsive">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Farmer</th>
                        <th>Buyer</th>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($deals as $deal)
                        <tr>
                            <td><strong>#{{ $deal->id }}</strong></td>
                            <td>{{ $deal->farmer?->name ?? 'N/A' }}</td>
                            <td>{{ $deal->buyer?->name ?? 'N/A' }}</td>
                            <td>{{ $deal->product?->name ?? 'N/A' }}</td>
                            <td>{{ $deal->quantity }}</td>
                            <td>
                                <span class="badge badge-{{ $deal->status }}">{{ ucfirst($deal->status) }}</span>
                            </td>
                            <td>
                                <small class="text-muted">
                                    {{ $deal->created_at->format('M d, Y') }}
                                </small>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editDealModal{{ $deal->id }}">
                                    <i class="bi bi-pencil"></i> Edit
                                </button>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <nav>
            <ul class="pagination justify-content-center">
                {{ $deals->links() }}
            </ul>
        </nav>
    @else
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No deals found.
        </div>
    @endif
</div>

<!-- Create Deal Modal -->
<div class="modal fade" id="createDealModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Deal</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('admin.deals.create') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="farmer_id" class="form-label">Farmer</label>
                        <select class="form-control" id="farmer_id" name="farmer_id" required>
                            <option value="">Select a farmer...</option>
                            @foreach (\App\Models\User::where('role', 'farmer')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="buyer_id" class="form-label">Buyer</label>
                        <select class="form-control" id="buyer_id" name="buyer_id" required>
                            <option value="">Select a buyer...</option>
                            @foreach (\App\Models\User::where('role', 'buyer')->get() as $user)
                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="product_id" class="form-label">Product</label>
                        <select class="form-control" id="product_id" name="product_id" required>
                            <option value="">Select a product...</option>
                            @foreach (\App\Models\Product::all() as $product)
                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" name="quantity" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label for="price" class="form-label">Unit Price</label>
                        <input type="number" class="form-control" id="price" name="price" step="0.01" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Deal</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('css')
<style>
    .badge-pending {
        background-color: #fbbf24;
        color: #78350f;
    }
    .badge-accepted {
        background-color: #d1fae5;
        color: #065f46;
    }
    .badge-completed {
        background-color: #a7f3d0;
        color: #065f46;
    }
    .badge-cancelled {
        background-color: #fee2e2;
        color: #7f1d1d;
    }
</style>
@endsection

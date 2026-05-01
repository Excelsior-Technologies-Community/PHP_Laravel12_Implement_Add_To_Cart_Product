@extends('layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4">Admin - Product Management</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    @endif

    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0">Add New Product</h5>
        </div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data">
                @csrf
                <div class="row g-3">
                    <div class="col-md-3">
                        <input type="text" name="name" class="form-control" placeholder="Product Name" required>
                    </div>
                    <div class="col-md-2">
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="price" class="form-control" placeholder="Price" step="0.01" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success w-100">Save Product</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive bg-white p-3 rounded shadow-sm">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                <tr>
                    <td>{{ $p->id }}</td>
                    <td>
                        @if($p->image)
                            <img src="{{ asset('images/' . $p->image) }}" width="50" height="50" style="object-fit: cover; border-radius: 5px;">
                        @else
                            <span class="text-muted">No Image</span>
                        @endif
                    </td>
                    <td><strong>{{ $p->name }}</strong></td>
                    <td>₹{{ number_format($p->price, 2) }}</td>
                    <td>
                        @if($p->status == 'active') 
                            <span class="badge bg-success">Active</span>
                        @elseif($p->status == 'inactive') 
                            <span class="badge bg-secondary">Inactive</span>
                        @else 
                            <span class="badge bg-danger">Deleted</span>
                        @endif
                    </td>
                    <td>
                        @if(!$p->trashed())
                            <button class="btn btn-sm btn-primary edit-btn" 
                                    data-id="{{ $p->id }}" 
                                    data-name="{{ $p->name }}" 
                                    data-price="{{ $p->price }}" 
                                    data-status="{{ $p->status }}"
                                    data-bs-toggle="modal" 
                                    data-bs-target="#editModal">
                                Edit
                            </button>

                            <form method="POST" action="{{ route('admin.products.destroy', $p->id) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-warning">Delete</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.products.restore', $p->id) }}" style="display:inline">
                                @csrf
                                <button class="btn btn-sm btn-success">Restore</button>
                            </form>
                            <form method="POST" action="{{ route('admin.products.forceDelete', $p->id) }}" style="display:inline">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-danger">Force Delete</button>
                            </form>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form id="editForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Product</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" name="name" id="edit_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" name="price" id="edit_price" class="form-control" step="0.01" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Change Image (Optional)</label>
                        <input type="file" name="image" class="form-control" accept="image/*">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" id="edit_status" class="form-select">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        $('.edit-btn').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');
            const price = $(this).data('price');
            const status = $(this).data('status');

            const url = "{{ url('admin/products') }}/" + id;
            $('#editForm').attr('action', url);
            
            $('#edit_name').val(name);
            $('#edit_price').val(price);
            $('#edit_status').val(status);
        });
    });
</script>
@endsection
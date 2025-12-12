@extends('layouts.app')

@section('content')
    <h1 class="mb-4">Admin - Products</h1>

    <table class="table">
        <thead>
            <tr>
                <th>ID</th>
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
                    <td>{{ $p->name }}</td>
                    <td>₹{{ number_format($p->price, 2) }}</td>
                    <td>
                        @if($p->status == 'active') <span class="badge bg-success">Active</span>
                        @elseif($p->status == 'inactive') <span class="badge bg-secondary">Inactive</span>
                        @else <span class="badge bg-danger">Deleted</span>
                        @endif
                    </td>
                    <td>
                        @if(!$p->trashed())
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
@endsection
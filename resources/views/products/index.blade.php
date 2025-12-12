@extends('layouts.app')

@section('content')
<h1 class="mb-4">Products</h1>

<div class="row">
    @foreach($products as $product)
    <div class="col-md-4 mb-3">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">{{ $product->name }}</h5>
                <p class="card-text">Price: ₹{{ number_format($product->price, 2) }}</p>

                {{-- Status badge --}}
                <p>
                  @if($product->status == 'active')
                    <span class="badge bg-success">Active</span>
                  @elseif($product->status == 'inactive')
                    <span class="badge bg-secondary">Inactive</span>
                  @else
                    <span class="badge bg-danger">Deleted</span>
                  @endif
                </p>

                <button class="btn btn-primary add-to-cart" data-id="{{ $product->id }}">Add to Cart</button>
            </div>
        </div>
    </div>
    @endforeach
</div>
@endsection

@push('scripts')
<script>
$(function(){
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('.add-to-cart').on('click', function(){
        var id = $(this).data('id');
        $.post("{{ route('cart.add') }}", { id: id }, function(response){
            if(response.status === 'success'){
                $('#cart-count').text(response.cart_count);
                // small visual feedback
                alert(response.message);
            } else {
                alert(response.message || 'Error');
            }
        }).fail(function(){
            alert('Request failed');
        });
    });
});
</script>
@endpush

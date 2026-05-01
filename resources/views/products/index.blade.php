@extends('layouts.app')

@section('content')
<h1 class="mb-4">Products</h1>

<input type="text" id="search" class="form-control mb-4" placeholder="Search products...">

<div id="product-list">
    <div class="row">
        @foreach($products as $product)
        <div class="col-md-4 mb-3">
            <div class="card shadow-sm h-100">
                <img src="{{ asset('images/' . $product->image) }}" 
                     class="card-img-top" 
                     height="200" 
                     style="object-fit: cover;">

                <div class="card-body d-flex flex-column">
                    <h5 class="card-title">{{ $product->name }}</h5>
                    <p class="card-text">Price: ₹{{ number_format($product->price, 2) }}</p>

                    <p>
                      @if($product->status == 'active')
                        <span class="badge bg-success">Active</span>
                      @elseif($product->status == 'inactive')
                        <span class="badge bg-secondary">Inactive</span>
                      @else
                        <span class="badge bg-danger">Deleted</span>
                      @endif
                    </p>

                    <button class="btn btn-primary add-to-cart mt-auto" data-id="{{ $product->id }}">
                        Add to Cart
                    </button>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <div class="mt-4 d-flex justify-content-center">
        {{ $products->appends(['search' => request('search')])->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
$(function(){

    $.ajaxSetup({
        headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
    });

    $(document).on('click', '.add-to-cart', function(){
        var id = $(this).data('id');

        $.post("{{ route('cart.add') }}", { id: id }, function(response){
            if(response.status === 'success'){
                $('#cart-count').text(response.cart_count);
                showToast(response.message);
            } else {
                showToast(response.message || 'Error');
            }
        }).fail(function(){
            showToast('Request failed');
        });
    });

    $('#search').on('keyup', function(){
        let value = $(this).val();

        $.get("{{ route('products.index') }}", { search: value }, function(data){
            $('#product-list').html($(data).find('#product-list').html());
        });
    });

});
</script>
@endpush
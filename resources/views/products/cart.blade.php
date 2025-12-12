@extends('layouts.app')

@section('content')
<h1 class="mb-4">Your Cart</h1>

@if(empty($cart) || count($cart) == 0)
    <div class="alert alert-info">Your cart is empty.</div>
@else
    <table class="table">
        <thead>
            <tr>
                <th>Product</th>
                <th width="120">Price</th>
                <th width="120">Quantity</th>
                <th width="150">Subtotal</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($cart as $item)
                @php $subtotal = $item['price'] * $item['quantity']; $total += $subtotal; @endphp
                <tr id="row-{{ $item['id'] }}">
                    <td>{{ $item['name'] }}</td>
                    <td>₹{{ number_format($item['price'], 2) }}</td>
                    <td>
                        <input type="number" min="1" class="form-control qty" data-id="{{ $item['id'] }}" value="{{ $item['quantity'] }}">
                    </td>
                    <td class="subtotal">₹{{ number_format($subtotal, 2) }}</td>
                    <td>
                        <button class="btn btn-danger remove-from-cart" data-id="{{ $item['id'] }}">Remove</button>
                    </td>
                </tr>
            @endforeach
            <tr>
                <td colspan="3" class="text-end"><strong>Total</strong></td>
                <td><strong>₹{{ number_format($total, 2) }}</strong></td>
                <td></td>
            </tr>
        </tbody>
    </table>
@endif
@endsection

@push('scripts')
<script>
$(function(){
    $.ajaxSetup({ headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') } });

    $('.qty').on('change', function(){
        var id = $(this).data('id');
        var quantity = $(this).val();
        $.post("{{ route('cart.update') }}", { id: id, quantity: quantity }, function(response){
            if(response.status === 'success'){
                location.reload();
            } else {
                alert(response.message);
            }
        }).fail(function(){ alert('Failed to update'); });
    });

    $('.remove-from-cart').on('click', function(){
        if(!confirm('Remove this item?')) return;
        var id = $(this).data('id');
        $.post("{{ route('cart.remove') }}", { id: id }, function(response){
            if(response.status === 'success'){
                location.reload();
            } else { alert(response.message); }
        }).fail(function(){ alert('Failed to remove'); });
    });
});
</script>
@endpush

@extends('layouts.app')

@section('content')
<h1 class="mb-4">Your Cart</h1>

@if(empty($cart) || count($cart) == 0)
    <div class="alert alert-info">Your cart is empty.</div>
@else

<button class="btn btn-danger mb-3" id="clear-cart">🗑 Clear Cart</button>

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
            <tr>
                <td>{{ $item['name'] }}</td>
                <td>₹{{ number_format($item['price'], 2) }}</td>
                <td>
                    <input type="number" min="1" class="form-control qty" data-id="{{ $item['id'] }}" value="{{ $item['quantity'] }}">
                </td>
                <td>₹{{ number_format($subtotal, 2) }}</td>
                <td>
                    <button class="btn btn-danger remove-from-cart" data-id="{{ $item['id'] }}">Remove</button>
                </td>
            </tr>
        @endforeach

        @php
            $gst = $total * 0.18;
            $grandTotal = $total + $gst;
        @endphp

        <tr>
            <td colspan="3" class="text-end"><strong>Subtotal</strong></td>
            <td>₹{{ number_format($total, 2) }}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" class="text-end"><strong>GST (18%)</strong></td>
            <td>₹{{ number_format($gst, 2) }}</td>
            <td></td>
        </tr>
        <tr>
            <td colspan="3" class="text-end"><strong>Grand Total</strong></td>
            <td><strong>₹{{ number_format($grandTotal, 2) }}</strong></td>
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
        $.post("{{ route('cart.update') }}", {
            id: $(this).data('id'),
            quantity: $(this).val()
        }, function(res){
            location.reload();
        });
    });

    $('.remove-from-cart').on('click', function(){
        if(!confirm('Remove item?')) return;

        $.post("{{ route('cart.remove') }}", {
            id: $(this).data('id')
        }, function(res){
            location.reload();
        });
    });

    $('#clear-cart').on('click', function(){
        if(!confirm('Clear entire cart?')) return;

        $.post("{{ route('cart.clear') }}", {}, function(res){
            location.reload();
        });
    });
});
</script>
@endpush
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // PUBLIC: Show all active products (not soft-deleted)
    public function index()
    {
        $products = Product::where('status', 'active')->get();
        return view('products.index', compact('products'));
    }

    // PUBLIC: Add product to cart (session-based). Expects AJAX POST { id }
    public function addToCart(Request $request)
    {
        $product = Product::where('id', $request->id)
                          ->where('status', 'active')
                          ->first();

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product unavailable.'], 404);
        }

        // Get cart from session, or empty array
        $cart = session()->get('cart', []);

        // Increase quantity if exists otherwise add
        if (isset($cart[$product->id])) {
            $cart[$product->id]['quantity']++;
        } else {
            $cart[$product->id] = [
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
            ];
        }

        session()->put('cart', $cart);

        // total item count
        $cartCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart',
            'cart_count' => $cartCount,
            'cart' => $cart
        ]);
    }

    // PUBLIC: Show cart page
    public function cart()
    {
        $cart = session()->get('cart', []);
        return view('products.cart', compact('cart'));
    }

    // PUBLIC: Update cart item quantity (post AJAX: { id, quantity })
    public function updateCart(Request $request)
    {
        $id = $request->id;
        $quantity = (int)$request->quantity;

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            if ($quantity > 0) {
                $cart[$id]['quantity'] = $quantity;
            } else {
                unset($cart[$id]);
            }

            session()->put('cart', $cart);
            return response()->json(['status' => 'success', 'message' => 'Cart updated', 'cart' => $cart]);
        }

        return response()->json(['status' => 'error', 'message' => 'Product not in cart'], 404);
    }

    // PUBLIC: Remove item from cart (post AJAX: { id })
    public function removeCart(Request $request)
    {
        $id = $request->id;
        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart', $cart);
            return response()->json(['status' => 'success', 'message' => 'Product removed', 'cart' => $cart]);
        }

        return response()->json(['status' => 'error', 'message' => 'Product not in cart'], 404);
    }

    // ---------- ADMIN ACTIONS (no auth guard added here; add middleware in real app) ----------

    // ADMIN: Show all products including trashed
    public function adminIndex()
{
    $products = Product::withTrashed()->get(); // fetch all, including deleted
    return view('admin.products.index', compact('products'));
}


    // ADMIN: Store new product
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'status' => 'nullable|in:active,inactive,deleted',
        ]);

        $data['created_by'] = auth()->id() ?? 1;
        $data['status'] = $data['status'] ?? 'active';

        Product::create($data);

        return redirect()->back()->with('success', 'Product created');
    }

    // ADMIN: Update product
    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return redirect()->back()->with('error', 'Product not found');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'status' => 'nullable|in:active,inactive,deleted',
        ]);

        $data['updated_by'] = auth()->id() ?? 1;
        $product->update($data);

        return redirect()->back()->with('success', 'Product updated');
    }

    // ADMIN: Soft delete product (move to trash)
   public function destroy($id)
{
    $product = Product::find($id);
    if (!$product) return redirect()->back()->with('error', 'Product not found');

    $product->status = 'deleted';  // mark status as deleted
    $product->save();

    $product->delete(); // soft delete
    return redirect()->back()->with('success', 'Product moved to trash');
}


    // ADMIN: Restore soft-deleted product
    public function restore($id)
    {
        $product = Product::withTrashed()->where('id', $id)->first();
        if ($product && $product->trashed()) {
            $product->restore();
            return redirect()->back()->with('success', 'Product restored');
        }
        return redirect()->back()->with('error', 'Product not found or not trashed');
    }

    // ADMIN: Permanently delete product
    public function forceDelete($id)
    {
        $product = Product::withTrashed()->where('id', $id)->first();
        if ($product) {
            $product->forceDelete();
            return redirect()->back()->with('success', 'Product permanently deleted');
        }
        return redirect()->back()->with('error', 'Product not found');
    }
}

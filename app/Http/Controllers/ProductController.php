<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\File;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::where('status', 'active');

        if ($request->search) {
            $query->where('name', 'LIKE', '%' . $request->search . '%');
        }

        $products = $query->paginate(6);

        return view('products.index', compact('products'));
    }

    public function addToCart(Request $request)
    {
        $product = Product::where('id', $request->id)
            ->where('status', 'active')
            ->first();

        if (!$product) {
            return response()->json(['status' => 'error', 'message' => 'Product unavailable.'], 404);
        }

        $cart = session()->get('cart', []);

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

        $cartCount = array_sum(array_column($cart, 'quantity'));

        return response()->json([
            'status' => 'success',
            'message' => 'Product added to cart',
            'cart_count' => $cartCount,
            'cart' => $cart
        ]);
    }

    public function cart()
    {
        $cart = session()->get('cart', []);
        return view('products.cart', compact('cart'));
    }

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

    public function adminIndex()
    {
        $products = Product::withTrashed()->get(); 
        return view('admin.products.index', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'status' => 'nullable|in:active,inactive,deleted',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $data['image'] = $imageName;
        }

        $data['created_by'] = auth()->id() ?? 1;
        $data['status'] = $data['status'] ?? 'active';

        Product::create($data);

        return redirect()->back()->with('success', 'Product created');
    }

    public function update(Request $request, $id)
    {
        $product = Product::find($id);
        if (!$product) return redirect()->back()->with('error', 'Product not found');

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric',
            'status' => 'nullable|in:active,inactive,deleted',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        if ($request->hasFile('image')) {
            if ($product->image && File::exists(public_path('images/' . $product->image))) {
                File::delete(public_path('images/' . $product->image));
            }
            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            $data['image'] = $imageName;
        }

        $data['updated_by'] = auth()->id() ?? 1;
        $product->update($data);

        return redirect()->back()->with('success', 'Product updated');
    }

    public function destroy($id)
    {
        $product = Product::find($id);
        if (!$product) return redirect()->back()->with('error', 'Product not found');

        $product->status = 'deleted';  
        $product->save();

        $product->delete(); 
        return redirect()->back()->with('success', 'Product moved to trash');
    }

    public function restore($id)
    {
        $product = Product::withTrashed()->where('id', $id)->first();
        if ($product && $product->trashed()) {
            $product->restore();
            return redirect()->back()->with('success', 'Product restored');
        }
        return redirect()->back()->with('error', 'Product not found or not trashed');
    }

    public function forceDelete($id)
    {
        $product = Product::withTrashed()->where('id', $id)->first();
        if ($product) {
            if ($product->image && File::exists(public_path('images/' . $product->image))) {
                File::delete(public_path('images/' . $product->image));
            }
            $product->forceDelete();
            return redirect()->back()->with('success', 'Product permanently deleted');
        }
        return redirect()->back()->with('error', 'Product not found');
    }

    public function clearCart()
    {
        session()->forget('cart');

        return response()->json([
            'status' => 'success',
            'message' => 'Cart cleared'
        ]);
    }
}
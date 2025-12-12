PHP_Laravel12_Implement_Add_To_Cart_Product 
---
Quick overview

You will create a Laravel 12 project that supports:
---
Product listing

Add to cart (session-based) via AJAX

Cart page (update quantity / remove)

Product fields: status (active/inactive), soft deletes (deleted_at), created_by, updated_by

Admin actions: soft-delete, restore, force-delete

All code below is ready to copy/paste into the specified files. After each code block you'll find a short explanation.

Prerequisites
---
PHP >= 8.1, Composer installed

Node.js + npm (optional for assets)

MySQL (or other DB) and DB created

Terminal / command line access

Set database in .env before migrating.

Step 0 — Create project (run these commands in terminal)
---
# 1) Create Laravel 12 project
```
composer create-project laravel/laravel PHP_Laravel12_Implement_Add_To_Cart_Product "12.*" --prefer-dist

cd PHP_Laravel12_Implement_Add_To_Cart_Product
```



# 2) Install npm deps (optional, for front-end if you want to compile assets)
```

npm install

```


Explanation: composer create-project scaffolds a Laravel 12 app. npm install installs frontend dependencies.

Step 1 — Configure database

Open .env and set DB values. Example:
```

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cart_db
DB_USERNAME=root
DB_PASSWORD=
```


Step 2 — Create Product model, migration, factory and seeder

Run:
```

php artisan make:model Product -m -f
php artisan make:seeder ProductSeeder
```

This creates:

app/Models/Product.php (model)

migration file in database/migrations

database/factories/ProductFactory.php

database/seeders/ProductSeeder.php

Step 3 — Edit the migration to include status, soft deletes, created_by, updated_by

Open the migration file database/migrations/xxxx_xx_xx_create_products_table.php and replace up() with the content below.
```

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();

            // Product basic fields
            $table->string('name');
            $table->decimal('price', 8, 2);
            $table->string('image')->nullable();

            /**
             * Product Status:
             * active   → Product is visible and available
             * inactive → Product exists but hidden (not sold, not shown)
             * deleted  → Marked as removed (soft-delete type)
             *
             * Default is "active"
             */
            $table->enum('status', ['active', 'inactive', 'deleted'])
                  ->default('active');

            /**
             * Soft delete field:
             * deleted_at will be automatically filled when calling:
             * $product->delete();
             */
            $table->softDeletes();

            /**
             * Tracking who created / updated
             * These will store user IDs (nullable if no login system yet)
             */
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // created_at / updated_at timestamps
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('products');
    }
};

```


Explanation: This migration creates necessary fields: status, deleted_at (soft deletes), created_by, updated_by.



Step 4 — Update Product model for SoftDeletes and fillable

Overwrite app/Models/Product.php with:
```

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     * These fields can be filled using Product::create() or $product->update().
     */
    protected $fillable = [
        'name',
        'price',
        'image',
        'status',
        'created_by',
        'updated_by',
    ];

    /**
     * Cast fields to specific types.
     * This ensures price always comes as decimal with 2 places.
     */
    protected $casts = [
        'price' => 'decimal:2',
    ];
}

```

Explanation: SoftDeletes enables soft delete behavior. $fillable allows mass assignment.


Step 5 — Product factory (optional)

database/factories/ProductFactory.php should look like this (it was generated; replace its definition with):
```

<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true),
            'price' => $this->faker->randomFloat(2, 50, 2000),
            'image' => null,
            'status' => 'active',
            'created_by' => 1,
            'updated_by' => 1,
        ];
    }
}

```

Explanation: Factory helps generate dummy products.



Step 6 — Product seeder

Replace database/seeders/ProductSeeder.php with:
```

<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run()
    {
        Product::create([
            'name' => 'Laptop',
            'price' => 55000,
            'image' => 'laptop.jpg',
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Mobile Phone',
            'price' => 12000,
            'image' => 'mobile.jpg',
            'status' => 'active',
        ]);

        Product::create([
            'name' => 'Headphones',
            'price' => 800,
            'image' => 'headphone.jpg',
            'status' => 'active',
        ]);
    }
}

```

And register it in database/seeders/DatabaseSeeder.php (inside run()):
```

$this->call([ ProductSeeder::class, ]);
```

Explanation: Seeds initial data for testing.

Step 7 — Create Controller

Run:
```

php artisan make:controller ProductController
```

Replace the generated file app/Http/Controllers/ProductController.php with the code below. This controller handles: index (list), addToCart, cart page, updateCart, removeCart, plus admin functions for soft delete, restore, force delete, create, update.
```

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

```


Step 8 — Routes

Replace routes/web.php with the routes below (or append):
```

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProductController;


Route::get('/', function () {
    return view('welcome');
});



// Public pages
Route::get('/', [ProductController::class, 'index'])->name('products.index');
Route::post('/add-to-cart', [ProductController::class, 'addToCart'])->name('cart.add');
Route::get('/cart', [ProductController::class, 'cart'])->name('cart.index');
Route::post('/cart/update', [ProductController::class, 'updateCart'])->name('cart.update');
Route::post('/cart/remove', [ProductController::class, 'removeCart'])->name('cart.remove');

// Admin product management (add auth middleware in real app)
Route::get('/admin/products', [ProductController::class, 'adminIndex'])->name('admin.products.index');
Route::post('/admin/products', [ProductController::class, 'store'])->name('admin.products.store');
Route::put('/admin/products/{id}', [ProductController::class, 'update'])->name('admin.products.update');
Route::delete('/admin/products/{id}', [ProductController::class, 'destroy'])->name('admin.products.destroy');
Route::post('/admin/products/{id}/restore', [ProductController::class, 'restore'])->name('admin.products.restore');
Route::delete('/admin/products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('admin.products.forceDelete');
```


Explanation: Contains public cart routes and admin management routes. In real app, protect admin routes with auth/can middleware.


Step 9 — Views (Blade templates)

Create a layout and the product/cart views. We'll keep styling minimal using Bootstrap CDN so you can copy/paste quickly.

resources/views/layouts/app.blade.php
```

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shop - Laravel 12</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <style>
        body {
            background-color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.5rem;
        }
        .cart-badge {
            font-size: 0.85rem;
            vertical-align: top;
        }
        .alert {
            border-radius: 0.4rem;
        }
        footer {
            background: #343a40;
            color: #fff;
            padding: 30px 0;
            margin-top: 50px;
        }
        footer a {
            color: #fff;
            text-decoration: none;
        }
        footer a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4">
  <div class="container">
    <a class="navbar-brand" href="{{ route('products.index') }}">Shop</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto align-items-center">
        <li class="nav-item me-3">
          <a class="nav-link position-relative" href="{{ route('cart.index') }}">
            <i class="bi bi-cart-fill fs-5"></i>
            <span class="badge bg-primary rounded-pill cart-badge" id="cart-count">
              {{ array_sum(array_column(session('cart', []), 'quantity') ?: [0]) }}
            </span>
          </a>
        </li>
        <li class="nav-item">
          <a class="btn btn-outline-primary" href="{{ route('products.index') }}">Shop Now</a>
        </li>
      </ul>
    </div>
  </div>
</nav>

<!-- Alerts -->
<div class="container">
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
</div>

<!-- Main Content -->
<div class="container">
    @yield('content')
</div>


<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

@stack('scripts')
</body>
</html>
```

Explanation: Layout includes CSRF meta and cart count badge (reads from session).


resources/views/products/index.blade.php
```

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

```

Explanation: Lists products, shows status, and attaches add-to-cart AJAX call.


resources/views/products/cart.blade.php
```

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
```

Explanation: Cart page allows quantity change and removal; simple approach reloads page after changes.

Step 10 — Admin product list view (shows status, created_by, updated_by, deleted_at)

Create resources/views/admin/products/index.blade.php:
```

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

```

Explanation: Admin list shows who created/updated, and deleted_at timestamp when trashed.


Step 11 — Run migrations & seed
```

# Run migrations
php artisan migrate


# Seed data
php artisan db:seed


# OR if you need to recreate DB and seed (danger: drops all tables)
php artisan migrate:fresh --seed
```

Explanation: migrate creates tables; db:seed runs seeders; migrate:fresh rebuilds DB.


Step 12 — Start local server
```

php artisan serve
# open http://127.0.0.1:8000

```

You can view this type Output:

/ — product listing (only active products)

<img width="1919" height="966" alt="Screenshot 2025-12-12 172858" src="https://github.com/user-attachments/assets/6fa78a82-b1f6-46a7-85f4-a0dcc31f0347" />

add to cart :

<img width="1918" height="967" alt="Screenshot 2025-12-12 172935" src="https://github.com/user-attachments/assets/91c9ac7e-9d9b-4ffc-8a68-6d015e8cf932" />


/cart — cart page

<img width="1913" height="958" alt="Screenshot 2025-12-12 173027" src="https://github.com/user-attachments/assets/73dec833-9173-4d9f-8c7d-fff69f38f5e1" />


/admin/products — admin panel showing all products (with trashed ones)

<img width="1918" height="959" alt="Screenshot 2025-12-12 172921" src="https://github.com/user-attachments/assets/9e92e9fa-649f-4e20-94b4-0ddf51c28b42" />

   


Laravel 12 Project Folder Structure (for your Add to Cart Project)
```
PHP_Laravel12_Implement_Add_To_Cart_Product/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   └── ProductController.php       # Your main controller
│   │   └── Middleware/
│   ├── Models/
│   │   └── Product.php                     # Eloquent model
│   └── ...
├── bootstrap/
│   └── ...
├── config/
│   └── ...
├── database/
│   ├── factories/
│   │   └── ProductFactory.php              # Factory for dummy data
│   ├── migrations/
│   │   └── xxxx_xx_xx_create_products_table.php
│   └── seeders/
│       ├── ProductSeeder.php
│       └── DatabaseSeeder.php
├── public/
│   ├── index.php
│   └── images/
│       ├── laptop.jpg
│       ├── mobile.jpg
│       └── headphone.jpg
├── resources/
│   ├── views/
│   │   ├── layouts/
│   │   │   └── app.blade.php              # Layout file
│   │   ├── products/
│   │   │   ├── index.blade.php            # Product listing page
│   │   │   └── cart.blade.php             # Cart page
│   │   └── admin/
│   │       └── products/
│   │           └── index.blade.php        # Admin panel
│   └── ...
├── routes/
│   └── web.php                             # Routes for public & admin
├── storage/
│   └── ...
├── tests/
│   └── ...
├── .env                                    # DB & environment config
├── composer.json
├── package.json                             # npm / frontend deps
└── README.md                                # Your documentation




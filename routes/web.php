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

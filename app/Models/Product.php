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

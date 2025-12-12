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

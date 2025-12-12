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

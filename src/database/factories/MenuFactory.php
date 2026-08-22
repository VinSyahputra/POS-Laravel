<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

class MenuFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Menu '.fake()->unique()->words(2, true),
            'price' => fake()->numberBetween(1000, 50000),
            'category_id' => Category::factory(),
        ];
    }
}

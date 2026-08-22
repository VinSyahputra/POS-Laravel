<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionItemFactory extends Factory
{
    public function definition(): array
    {
        $price = fake()->numberBetween(1000, 50000);
        $qty = fake()->numberBetween(1, 5);

        return [
            'transaction_id' => Transaction::factory(),
            'menu_id' => null,
            'menu_name' => 'Menu '.fake()->words(2, true),
            'price' => $price,
            'qty' => $qty,
            'subtotal' => $price * $qty,
        ];
    }
}

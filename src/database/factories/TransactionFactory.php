<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(10000, 500000);

        return [
            'receipt_number' => 'NOTA-TEST-'.fake()->unique()->numerify('####'),
            'cashier_name' => fake()->optional()->firstName(),
            'subtotal' => $subtotal,
            'discount' => 0,
            'tax' => 0,
            'total' => $subtotal,
            'payment_amount' => $subtotal,
            'change_amount' => 0,
            'payment_method' => 'cash',
            'transaction_time' => now(),
        ];
    }
}

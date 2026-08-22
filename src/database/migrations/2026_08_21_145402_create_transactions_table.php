<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('receipt_number')->unique();
            $table->string('cashier_name')->nullable();
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('discount')->default(0);
            $table->unsignedBigInteger('tax')->default(0);
            $table->unsignedBigInteger('total');
            $table->unsignedBigInteger('payment_amount');
            $table->unsignedBigInteger('change_amount');
            $table->string('payment_method')->default('cash');
            $table->timestamp('transaction_time');
            $table->timestamps();

            $table->index('transaction_time');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};

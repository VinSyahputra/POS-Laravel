<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('order_no', 50)->nullable()->after('receipt_number');
            $table->string('table_no', 20)->nullable()->after('cashier_name');
            $table->string('mode', 20)->default('DINE IN')->after('table_no');
            $table->dateTime('entry_time')->nullable()->after('transaction_time');
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn(['order_no', 'table_no', 'mode', 'entry_time']);
        });
    }
};

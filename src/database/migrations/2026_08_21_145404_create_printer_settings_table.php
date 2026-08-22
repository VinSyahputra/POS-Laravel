<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('printer_settings', function (Blueprint $table) {
            $table->id();
            $table->string('printer_name')->nullable();
            $table->string('paper_width')->default('80mm');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('printer_settings');
    }
};

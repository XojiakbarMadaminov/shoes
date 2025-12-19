<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exchange_operations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('in_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('out_product_id')->constrained('products')->restrictOnDelete();
            $table->bigInteger('price_difference')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exchange_operations');
    }
};

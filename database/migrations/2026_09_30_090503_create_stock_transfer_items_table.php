<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_transfer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('to_product_id')->constrained('products')->restrictOnDelete();
            $table->foreignId('from_product_size_id')->nullable()->constrained('product_sizes')->restrictOnDelete();
            $table->foreignId('to_product_size_id')->nullable()->constrained('product_sizes')->restrictOnDelete();
            $table->string('product_name');
            $table->string('barcode')->nullable();
            $table->string('variant')->nullable();
            $table->unsignedBigInteger('quantity');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};

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
        Schema::table('products', function (Blueprint $table) {
            // Add store reference (nullable to avoid breaking existing data)
            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            // Drop existing unique index on barcode to allow duplicates across stores
            $table->dropUnique('products_barcode_unique');

            // Add composite unique index to enforce uniqueness per store
            $table->unique(['store_id', 'barcode'], 'products_store_barcode_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Drop composite unique index
            $table->dropUnique('products_store_barcode_unique');

            // Restore global unique on barcode
            $table->unique('barcode');

            // Drop store reference
            $table->dropConstrainedForeignId('store_id');
        });
    }
};

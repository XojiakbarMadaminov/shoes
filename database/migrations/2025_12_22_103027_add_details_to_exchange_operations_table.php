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
        Schema::table('exchange_operations', function (Blueprint $table) {
            $table->foreignId('in_product_size_id')
                ->nullable()
                ->after('in_product_id')
                ->constrained('product_sizes')
                ->nullOnDelete();

            $table->foreignId('out_product_size_id')
                ->nullable()
                ->after('out_product_id')
                ->constrained('product_sizes')
                ->nullOnDelete();

            $table->foreignId('handled_by')
                ->nullable()
                ->after('price_difference')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exchange_operations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropConstrainedForeignId('out_product_size_id');
            $table->dropConstrainedForeignId('in_product_size_id');
        });
    }
};

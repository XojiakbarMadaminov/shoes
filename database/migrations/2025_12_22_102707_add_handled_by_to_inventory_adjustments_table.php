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
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->foreignId('product_size_id')
                ->nullable()
                ->after('product_id')
                ->constrained('product_sizes')
                ->nullOnDelete();

            $table->foreignId('handled_by')
                ->nullable()
                ->after('product_size_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_adjustments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('handled_by');
            $table->dropConstrainedForeignId('product_size_id');
        });
    }
};

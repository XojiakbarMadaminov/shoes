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
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sales', 'product_discount_total')) {
                $table->decimal('product_discount_total', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sales', 'order_discount_total')) {
                $table->decimal('order_discount_total', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sales', 'discount_total')) {
                $table->decimal('discount_total', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sales', 'applied_discounts')) {
                $table->json('applied_discounts')->nullable();
            }
        });

        Schema::table('sale_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_items', 'subtotal_amount')) {
                $table->decimal('subtotal_amount', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sale_items', 'product_discount_total')) {
                $table->decimal('product_discount_total', 15, 2)->default(0);
            }

            if (!Schema::hasColumn('sale_items', 'applied_discounts')) {
                $table->json('applied_discounts')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('sale_items', 'applied_discounts') ? 'applied_discounts' : null,
                Schema::hasColumn('sale_items', 'product_discount_total') ? 'product_discount_total' : null,
                Schema::hasColumn('sale_items', 'subtotal_amount') ? 'subtotal_amount' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('sales', 'applied_discounts') ? 'applied_discounts' : null,
                Schema::hasColumn('sales', 'discount_total') ? 'discount_total' : null,
                Schema::hasColumn('sales', 'order_discount_total') ? 'order_discount_total' : null,
                Schema::hasColumn('sales', 'product_discount_total') ? 'product_discount_total' : null,
                Schema::hasColumn('sales', 'subtotal_amount') ? 'subtotal_amount' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};

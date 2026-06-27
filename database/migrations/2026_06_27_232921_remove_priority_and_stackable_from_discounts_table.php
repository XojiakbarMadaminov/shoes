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
        $hasPriority  = Schema::hasColumn('discounts', 'priority');
        $hasStackable = Schema::hasColumn('discounts', 'stackable');

        if (!$hasPriority && !$hasStackable) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (Schema::hasColumn('discounts', 'priority')) {
                $table->dropColumn('priority');
            }

            if (Schema::hasColumn('discounts', 'stackable')) {
                $table->dropColumn('stackable');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasPriority  = Schema::hasColumn('discounts', 'priority');
        $hasStackable = Schema::hasColumn('discounts', 'stackable');

        if ($hasPriority && $hasStackable) {
            return;
        }

        Schema::table('discounts', function (Blueprint $table) {
            if (!Schema::hasColumn('discounts', 'priority')) {
                $table->integer('priority')->default(0)->index();
            }

            if (!Schema::hasColumn('discounts', 'stackable')) {
                $table->boolean('stackable')->default(false)->index();
            }
        });
    }
};

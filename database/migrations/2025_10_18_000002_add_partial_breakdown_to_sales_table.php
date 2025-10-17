<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('mixed_cash_amount', 15, 2)->default(0)->after('payment_type');
            $table->decimal('mixed_card_amount', 15, 2)->default(0)->after('mixed_cash_amount');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['mixed_cash_amount', 'mixed_card_amount']);
        });
    }
};

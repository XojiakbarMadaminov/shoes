<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_debt_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('supplier_debt_id')->constrained('supplier_debts')->cascadeOnDelete();
            $table->foreignId('purchase_id')->nullable()->constrained('purchases')->nullOnDelete();
            $table->enum('type', ['debt', 'payment']);
            $table->decimal('amount', 15, 2);
            $table->dateTime('date');
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_debt_transactions');
    }
};

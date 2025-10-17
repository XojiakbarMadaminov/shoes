<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_payment_type_check');
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_payment_type_check CHECK (payment_type IN ('cash','card','debt','transfer','partial'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE sales DROP CONSTRAINT IF EXISTS sales_payment_type_check');
        DB::statement("ALTER TABLE sales ADD CONSTRAINT sales_payment_type_check CHECK (payment_type IN ('cash','card','debt'))");
    }
};

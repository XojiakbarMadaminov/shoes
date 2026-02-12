<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_type_check');
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ('size','package','color'))");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE products DROP CONSTRAINT IF EXISTS products_type_check');
        DB::statement("ALTER TABLE products ADD CONSTRAINT products_type_check CHECK (type IN ('size','package'))");
    }
};

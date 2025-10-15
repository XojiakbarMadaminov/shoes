<?php

namespace App\Models\Traits;

use Illuminate\Database\Eloquent\Builder;

trait HasCurrentStoreScope
{
    protected static function bootHasCurrentStoreScope(): void
    {
        static::addGlobalScope('current_store', function (Builder $builder) {
            $user = auth()->user();

            if (!$user || !$user->current_store_id) {
                return;
            }

            $model  = new static;
            $table  = $model->getTable();
            $schema = $model->getConnection()->getSchemaBuilder();

            // ✅ 1. Agar jadvalda store_id ustuni mavjud bo‘lsa – to‘g‘ridan-to‘g‘ri filter
            if ($schema->hasColumn($table, 'store_id')) {
                $builder->where("{$table}.store_id", $user->current_store_id);

                return;
            }

            // ✅ 2. Stocks – faqat joriy store bilan bog‘langan omborlar
            if ($table === 'stocks') {
                $builder->whereHas('stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });

                return;
            }

            // ✅ 3. Products – joriy store omborlarida mavjud productlar
            if ($table === 'products') {
                $builder->whereHas('sizes.stocks.stock.stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });

                return;
            }

            // ✅ 4. ProductSizes – faqat joriy store’da mavjud bo‘lgan tovar razmerlari
            if ($table === 'product_sizes') {
                $builder->whereHas('product.sizes.stocks.stock.stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });

                return;
            }

            // ✅ 5. ProductSizeStocks – faqat joriy store omborlariga tegishli yozuvlar
            if ($table === 'product_size_stocks') {
                $builder->whereHas('stock.stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });

                return;
            }

            // ✅ 6. SaleItems – tegishli store orqali filter
            if ($table === 'sale_items') {
                $builder->whereHas('sale', function ($q) use ($user) {
                    $q->where('store_id', $user->current_store_id);
                });

                return;
            }
        });
    }
}

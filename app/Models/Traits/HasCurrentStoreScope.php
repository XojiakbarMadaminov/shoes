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

            $table = (new static)->getTable();

            if (in_array('store_id', (new static)->getConnection()->getSchemaBuilder()->getColumnListing($table))) {
                $builder->where($table . '.store_id', $user->current_store_id);
            }

            if ($table === 'stocks') {
                $builder->whereHas('stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });
            }

            if ($table === 'products') {
                $builder->whereHas('productStocks.stock.stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });
            }

            if ($table === 'product_stocks') {
                $builder->whereHas('stock.stores', function ($q) use ($user) {
                    $q->where('stores.id', $user->current_store_id);
                });
            }

            if ($table === 'sale_items') {
                $builder->whereHas('sale', function ($q) use ($user) {
                    $q->where('store_id', $user->current_store_id);
                });
            }


        });
    }
}

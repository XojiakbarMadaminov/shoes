<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasCurrentStoreScope, SoftDeletes;

    protected $table   = 'stocks';
    protected $guarded = [];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_stock');
    }

    public function productSizeStocks(): HasMany
    {
        return $this->hasMany(ProductSizeStock::class);
    }

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    #[Scope]
    public function active($query)
    {
        return $query->where('is_active', true);
    }

    protected static function boot()
    {
        parent::boot();

        static::deleting(function (Stock $stock) {
            cache()->forget('active_stocks_for_store_' . auth()->id());
        });

        static::updated(function (Stock $stock) {
            if ($stock->isDirty('is_active')) {
                cache()->forget('active_stocks_for_store_' . auth()->id());
            }
        });
    }
}

<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes, HasCurrentStoreScope;
    protected $table = 'products';
    protected $guarded = [];

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class);
    }

    public function stocks(): BelongsToMany
    {
        return $this->belongsToMany(Stock::class, ProductStock::class)
            ->withPivot('quantity')
            ->withTimestamps();
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->productStocks->sum('quantity');
    }
}

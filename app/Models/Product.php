<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasCurrentStoreScope, SoftDeletes;

    protected $table   = 'products';
    protected $guarded = [];

    public function sizes()
    {
        return $this->hasMany(ProductSize::class);
    }

    public function color()
    {
        return $this->belongsTo(Color::class);
    }

    public function sizeStocks()
    {
        return $this->hasManyThrough(
            ProductSizeStock::class,
            ProductSize::class,
            'product_id',
            'product_size_id',
            'id',
            'id'
        );
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        return $this->productStocks->sum('quantity');
    }
}

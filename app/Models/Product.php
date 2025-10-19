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

    public const TYPE_SIZE = 'size';
    public const TYPE_PACKAGE = 'package';

    protected $casts = [
        'type' => 'string',
    ];

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

    public function productStocks(): HasMany
    {
        return $this->hasMany(ProductStock::class, 'product_id');
    }

    public function saleItems(): HasMany
    {
        return $this->hasMany(SaleItem::class, 'product_id');
    }

    public function purchaseItems(): HasMany
    {
        return $this->hasMany(PurchaseItem::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function getTotalQuantityAttribute(): int
    {
        // Prefer new unified product_stocks table when available
        $sum = $this->productStocks()->sum('quantity');

        if ($sum > 0) {
            return (int) $sum;
        }

        // Backward compatibility: sum over size-based legacy stocks if any
        return (int) $this->sizeStocks()->sum('quantity');
    }

    public function isSizeBased(): bool
    {
        return ($this->type ?? self::TYPE_SIZE) === self::TYPE_SIZE;
    }

    public function isPackageBased(): bool
    {
        return ($this->type ?? self::TYPE_SIZE) === self::TYPE_PACKAGE;
    }
}

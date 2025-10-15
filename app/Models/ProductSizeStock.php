<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductSizeStock extends Model
{
    protected $table   = 'product_size_stocks';
    protected $guarded = [];

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function product()
    {
        return $this->hasOneThrough(
            Product::class,
            ProductSize::class,
            'id',
            'id',
            'product_size_id',
            'product_id'
        );
    }
}

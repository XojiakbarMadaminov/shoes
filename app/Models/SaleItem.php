<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCurrentStoreScope;

class SaleItem extends Model
{
    use HasCurrentStoreScope;

    protected $table   = 'sale_items';
    protected $guarded = [];

    protected $casts = [
        'quantity'               => 'integer',
        'price'                  => 'float',
        'subtotal_amount'        => 'float',
        'product_discount_total' => 'float',
        'total'                  => 'float',
        'applied_discounts'      => 'array',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function productSize()
    {
        return $this->belongsTo(ProductSize::class, 'product_size_id');
    }
}

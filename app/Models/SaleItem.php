<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleItem extends Model
{
    protected $table = 'sale_items';
    protected $guarded = [];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }
}

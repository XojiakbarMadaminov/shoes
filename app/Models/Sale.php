<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $table = 'sales';
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}

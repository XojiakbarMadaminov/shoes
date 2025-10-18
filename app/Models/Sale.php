<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\HasCurrentStoreScope;

class Sale extends Model
{
    use HasCurrentStoreScope;

    protected $table   = 'sales';
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function itemsDistinctName()
    {
        return $this->hasMany(SaleItem::class)->get()->unique('product_id');
    }



}

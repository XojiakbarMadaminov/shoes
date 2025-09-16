<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Stock extends Model
{
    use HasCurrentStoreScope;

    protected $table = 'stocks';
    protected $guarded = [];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_stock');
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
}

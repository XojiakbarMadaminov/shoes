<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
    use HasCurrentStoreScope;

    protected $table = 'stocks';
    protected $guarded = [];

    public function stores()
    {
        return $this->belongsToMany(Store::class, 'store_stock');
    }
}

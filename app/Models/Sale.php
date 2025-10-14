<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasCurrentStoreScope;
    protected $table = 'sales';
    protected $guarded = [];

    public function items()
    {
        return $this->hasMany(SaleItem::class);
    }
}

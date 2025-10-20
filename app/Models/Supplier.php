<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Supplier extends Model
{
    protected $table   = 'suppliers';
    protected $guarded = [];

    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class);
    }

    public function debt(): HasOne
    {
        return $this->hasOne(SupplierDebt::class);
    }
}

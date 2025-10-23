<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Supplier extends Model
{
    use HasFactory;

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

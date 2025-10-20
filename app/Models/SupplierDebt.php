<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SupplierDebt extends Model
{
    use HasCurrentStoreScope;

    protected $table   = 'supplier_debts';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(SupplierDebtTransaction::class);
    }

    public function latestTransaction(): HasOne
    {
        return $this->hasOne(SupplierDebtTransaction::class)->latestOfMany('date');
    }
}

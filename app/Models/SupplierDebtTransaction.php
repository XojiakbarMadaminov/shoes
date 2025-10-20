<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierDebtTransaction extends Model
{
    protected $table   = 'supplier_debt_transactions';
    protected $guarded = [];

    protected $casts = [
        'amount' => 'float',
        'date'   => 'datetime',
    ];

    public function debt(): BelongsTo
    {
        return $this->belongsTo(SupplierDebt::class, 'supplier_debt_id');
    }

    public function purchase(): BelongsTo
    {
        return $this->belongsTo(Purchase::class);
    }

}

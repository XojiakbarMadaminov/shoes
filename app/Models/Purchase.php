<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    use HasCurrentStoreScope;

    protected $table   = 'purchases';
    protected $guarded = [];

    protected $casts = [
        'purchase_date'    => 'date',
        'total_amount'     => 'float',
        'paid_amount'      => 'float',
        'remaining_amount' => 'float',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }
}

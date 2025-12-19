<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAdjustment extends Model
{
    public const TYPE_RETURN       = 'return';
    public const TYPE_EXCHANGE_IN  = 'exchange_in';
    public const TYPE_EXCHANGE_OUT = 'exchange_out';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
        'unit_price' => 'int',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

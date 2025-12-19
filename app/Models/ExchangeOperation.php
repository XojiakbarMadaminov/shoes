<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExchangeOperation extends Model
{
    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function inProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'in_product_id');
    }

    public function outProduct(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'out_product_id');
    }
}

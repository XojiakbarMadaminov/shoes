<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransfer extends Model
{
    public const MODE_SELECTED = 'selected';

    public const MODE_ALL = 'all';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'total_quantity' => 'integer',
        ];
    }

    public function fromStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'from_store_id');
    }

    public function toStore(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'to_store_id');
    }

    public function fromStock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'from_stock_id');
    }

    public function toStock(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'to_stock_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }
}

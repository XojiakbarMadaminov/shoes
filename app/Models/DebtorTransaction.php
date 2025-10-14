<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DebtorTransaction extends Model
{
    protected $table = 'debtor_transactions';
    protected $guarded = [];

    public function debtor()
    {
        return $this->belongsTo(Debtor::class);
    }
}

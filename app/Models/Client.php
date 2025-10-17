<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $table   = 'clients';
    protected $guarded = [];

    public function debtor()
    {
        return $this->hasOne(Debtor::class);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }
}

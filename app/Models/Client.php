<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;
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

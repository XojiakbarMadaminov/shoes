<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Store extends Model
{
    use SoftDeletes;

    protected $table   = 'stores';
    protected $guarded = [];

    public function stocks()
    {
        return $this->belongsToMany(Stock::class, 'store_stock');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'store_user', 'store_id', 'user_id')->withPivot('role')->withTimestamps();
    }

    public function debtors()
    {
        return $this->hasMany(Debtor::class);
    }
}

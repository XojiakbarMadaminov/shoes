<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Attributes\Scope;

class Category extends Model
{
    protected $table   = 'categories';
    protected $guarded = [];

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    #[Scope]
    public function active($query)
    {
        return $query->where('is_active', true);
    }
}

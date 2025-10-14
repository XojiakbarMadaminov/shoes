<?php

namespace App\Models;

use App\Models\Traits\HasCurrentStoreScope;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $table = 'categories';
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

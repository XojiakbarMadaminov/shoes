<?php

namespace App\Models;

use Spatie\MediaLibrary\HasMedia;
use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\Attributes\Scope;

class Category extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $table   = 'categories';
    protected $guarded = [];

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile();
    }

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

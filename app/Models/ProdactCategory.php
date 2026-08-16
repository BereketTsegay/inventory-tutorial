<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProdactCategory extends Model
{
    //
    protected $guarded = [];

    protected static function boot() : void
    {
        parent::boot();

        static::saving(function ($category) {
            if (empty($category->slug)) {
                $category->slug = \Str::slug($category->name);
            }
        });
    }
}

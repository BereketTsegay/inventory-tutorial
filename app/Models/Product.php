<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    //
    protected $guarded = [];

    protected $casts = [
        'image' => 'array', // <-- Automatically encodes/decodes array maps back and forth from JSON
    ];

    public function images()
    {
        return $this->hasMany(ProductImage::class);
    }


    public function category()
    {
        return $this->belongsTo(ProdactCategory::class, 'category_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function getImageUrlAttribute()
    {
        return $this->images()->first() ? asset($this->images()->first()->image) : null;
    }

    public function getImagePathAttribute()
    {
        return $this->images()->first() ? $this->images()->first()->image : null;
    }

    public function warehouse(){
        return $this->belongsTo(WareHouse::class, 'warehouse_id','id');
    }
}

<?php

namespace App\Models;

use App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name',
    'slug',
    'price',
    'stock',
    'status',
    'category_id',];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}

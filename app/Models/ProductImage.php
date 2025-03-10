<?php

namespace App\Models;

use App\Models\Product;

use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{

    public function products()
    {
        return $this->belongsTo(Product::class);
    }
}

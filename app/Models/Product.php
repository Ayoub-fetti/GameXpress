<?php

namespace App\Models;

use App\Models\User;
use App\Models\Category;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name','slug','price','stock','status'];


    public  function users()
    {
        return $this->belongsTo(User::class);
    }
    public  function categories()
    {
        return $this->belongsTo(Category::class);
    }
}

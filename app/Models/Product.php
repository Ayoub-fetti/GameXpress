<?php

namespace App\Models;

use App\Notifications\StockNotification;
use Illuminate\Support\Facades\Notification;

use App\Models\User;
use App\Models\Category;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name','slug','price','stock','status', 'category_id'];


    public  function users()
    {
        return $this->belongsTo(User::class);
    }
    public  function categories()
    {
        return $this->belongsTo(Category::class);
    }
    
    protected static function boot()
        {
            parent::boot();

            static::updated(function ($product) {
                if ($product->stock <= 10) { 
                    $admins = User::role('super_admin')->get(); 
                    Notification::send($admins, new StockNotification($product));
                }
            });
        }
}

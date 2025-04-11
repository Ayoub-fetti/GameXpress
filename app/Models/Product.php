<?php

namespace App\Models;

use App\Notifications\StockNotification;
use Illuminate\Support\Facades\Notification;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name','slug','price','stock','status', 'category_id'];


    public  function users()
    {
        return $this->belongsTo(User::class);
    }
    public  function categories()
    {
        return $this->belongsTo(Category::class);
    }
    public function images(){
        return $this->hasMany(ProductImage::class);
    }
    
    protected static function boot()
        {
            parent::boot();

            static::updated(function ($product) {
                if ($product->stock <= 10) { 
                    $admins = User::role('super_admin')->get(); 
                    Notification::send($admins, new  ($product));
                }
            });
        }

    


}

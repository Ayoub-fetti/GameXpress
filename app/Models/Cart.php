<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'session_id',
        'total_amount',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    public function items()
    {
        return $this->hasMany(CartItem::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function updateTotalAmount()
    {
        $this->total_amount = $this->items->sum('total_price');
        $this->save();

        return $this;
    }

    public function isExpired()
    {
        return $this->expires_at && Carbon::now()->greaterThan($this->expires_at);
    }
}


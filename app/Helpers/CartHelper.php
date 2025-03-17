<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;



class CartHelper
{

    public static function calculateCartTotals(Cart $cart): Cart
    {

        $cart->subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $priceAfterDiscount = $cart->subtotal - $cart->discount_amount;

        $cart->tax_amount = $priceAfterDiscount * ($cart->tax_rate / 100);

        $cart->total_amount = $priceAfterDiscount + $cart->tax_amount;

        $cart->save();

        return $cart;
    }


    public static function updateCartItemTotal(CartItem $item): CartItem
    {
        $item->total_price = $item->quantity * $item->unit_price;
        $item->save();

        self::calculateCartTotals($item->cart);

        return $item;
    }


  
}

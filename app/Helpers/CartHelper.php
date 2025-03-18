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

    public static function applyDiscount(Cart $cart, float $amount): Cart
    {
        $cart->discount_amount = $amount;
        return self::calculateCartTotals($cart);
    }


    public static function setTaxRate(Cart $cart, float $rate): Cart
    {
        $cart->tax_rate = $rate;
        return self::calculateCartTotals($cart);
    }


    public static function updateCartItemTotal(CartItem $item): CartItem
    {
        $item->total_price = $item->quantity * $item->unit_price;
        $item->save();

        self::calculateCartTotals($item->cart);

        return $item;
    }


    public static function isCartExpired(Cart $cart): bool
    {
        return $cart->expires_at && Carbon::now()->greaterThan($cart->expires_at);
    }


    public static function getCartTotals(Cart $cart): array
    {
        return [
            'subtotal' => round($cart->subtotal, 2),
            'discount' => round($cart->discount_amount, 2),
            'price_after_discount' => round($cart->subtotal - $cart->discount_amount, 2),
            'tax_rate' => round($cart->tax_rate, 2),
            'tax' => round($cart->tax_amount, 2),
            'total' => round($cart->total_amount, 2),
        ];
    }

} 
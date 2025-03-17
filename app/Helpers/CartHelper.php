<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class CartHelper
{
    /**
     * Calculate all cart totals including tax and discounts
     */
    public static function calculateCartTotals(Cart $cart): Cart
    {

        $cart->subtotal = $cart->items->sum(function ($item) {
            return $item->quantity * $item->unit_price;
        });

        $cart->save();

        return $cart;
    }

    /**
     * Update cart item total price
     */
    public static function updateCartItemTotal(CartItem $item): CartItem
    {
        $item->total_price = $item->quantity * $item->unit_price;
        $item->save();

        self::calculateCartTotals($item->cart);

        return $item;
    }


    /**
     * Format the price with currency symbol
     */
    public static function formatPrice(float $amount): string
    {
        return number_format($amount, 2) . ' MAD';
    }
} 
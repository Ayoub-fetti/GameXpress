<?php

namespace App\Helpers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\PromoCode;
use App\Models\PromoCodeUsage;
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

    public static function applyPromoCode(Cart $cart, string $promoCode): array
    {
        
        $promoCode = PromoCode::where('code', $promoCode)
            ->where('is_active', true)
            ->where(function($query) {
                $query->where('expires_at', '>', now())
                    ->orWhereNull('expires_at');
            })
            ->first();

        if (!$promoCode) {
            return [
                'success' => false,
                'message' => 'Code promo invalide ou expiré'
            ];
        }

        if (!$promoCode->isValid()) {
            return [
                'success' => false,
                'message' => 'Code promo non valide'
            ];
        }

        
        if ($promoCode->max_uses > 0) {
            $totalUses = PromoCodeUsage::where('promo_code_id', $promoCode->id)->count();
            if ($totalUses >= $promoCode->max_uses) {
                return [
                    'success' => false,
                    'message' => 'Ce code promo a atteint son nombre maximum d\'utilisations'
                ];
            }
        }

        
        if ($cart->user_id && $promoCode->max_uses_per_user > 0) {
            $userUses = PromoCodeUsage::where('promo_code_id', $promoCode->id)
                ->where('user_id', $cart->user_id)
                ->count();
            
            if ($userUses >= $promoCode->max_uses_per_user) {
                return [
                    'success' => false,
                    'message' => 'Vous avez déjà utilisé ce code promo le nombre maximum de fois'
                ];
            }
        }

        
        $discountAmount = self::calculatePromoDiscount($cart, $promoCode);

        
        self::applyDiscount($cart, $discountAmount);

        
        if ($cart->user_id) {
            PromoCodeUsage::create([
                'promo_code_id' => $promoCode->id,
                'user_id' => $cart->user_id,
                'cart_id' => $cart->id,
                'discount_amount' => $discountAmount
            ]);
        }

        return [
            'success' => true,
            'message' => 'Code promo appliqué avec succès',
            'discount_amount' => $discountAmount,
            'cart_totals' => self::getCartTotals($cart)
        ];
    }

    private static function calculatePromoDiscount(Cart $cart, PromoCode $promoCode): float
    {
        $discountAmount = 0;

        if ($promoCode->discount_type === 'percentage') {
            $discountAmount = $cart->subtotal * ($promoCode->discount_value / 100);

        } else {
            $discountAmount = min($promoCode->discount_value, $cart->subtotal);
        }

        return round($discountAmount, 2);
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
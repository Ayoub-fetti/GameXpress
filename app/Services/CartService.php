<?php

namespace App\Services;

use App\Models\CartItem;
use Illuminate\Support\Collection;

class CartService
{
    private const TAX_RATE = 0.20; // 20% TVA

    /**
     * Calculate the cart total including tax
     *
     * @param Collection|CartItem[] $cartItems
     * @return array
     */
    public function calculateTotal(Collection $cartItems): array
    {
        $subtotal = $this->calculateSubtotal($cartItems);
        $tax = $this->calculateTax($subtotal);
        $total = $subtotal + $tax;

        return [
            'subtotal' => round($subtotal, 2),
            'tax' => round($tax, 2),
            'total' => round($total, 2),
            'tax_rate' => self::TAX_RATE * 100,
        ];
    }

    /**
     * Calculate the subtotal of all items in the cart
     *
     * @param Collection|CartItem[] $cartItems
     * @return float
     */
    private function calculateSubtotal(Collection $cartItems): float
    {
        return $cartItems->sum(function ($item) {
            return $item->quantity * $item->product->price;
        });
    }

    /**
     * Calculate the tax amount based on the subtotal
     *
     * @param float $subtotal
     * @return float
     */
    private function calculateTax(float $subtotal): float
    {
        return $subtotal * self::TAX_RATE;
    }

    /**
     * Format the price with currency symbol
     *
     * @param float $amount
     * @return string
     */
    public function formatPrice(float $amount): string
    {
        return number_format($amount, 2) . ' MAD';
    }
} 
<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function createFromCart(Request $request)
    {
        $request->validate([
            'session_id' => 'required_if:guest,true|string',
            'guest' => 'boolean'
        ]);

        if (Auth::guard('sanctum')->check()) {
            $cart = Cart::with('items.product')->where('user_id', Auth::guard('sanctum')->id())->first();
        } else {
            $cart = Cart::with('items.product')->where('session_id', $request->session_id)->first();
        }

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        if ($cart->total_amount <= 0) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        // Prepare products data from all cart items
        $products = $cart->items->map(function ($cartItem) {
            return [
                'product_id' => $cartItem->product->id,
                'product_name' => $cartItem->product->name,
                'quantity' => $cartItem->quantity,
                'unit_price' => $cartItem->product->price,
                'subtotal' => $cartItem->quantity * $cartItem->product->price,
                'tax_rate' => $cartItem->product->tax_rate ?? 20.00,
                'tax_amount' => ($cartItem->product->price * ($cartItem->product->tax_rate ?? 20.00) / 100) * $cartItem->quantity,
                'discount_amount' => $cartItem->discount_amount ?? 0
            ];
        })->toArray();

        $orderData = [
            'user_id' => $cart->user_id,
            'products' => $products,
            'subtotal' => $cart->items->sum(function ($item) {
                return $item->quantity * $item->product->price;
            }),
            'total_price' => $cart->total_amount,
            'status' => 'pending',
            'tax_rate' => $cart->tax_rate,
            'tax_amount' => $cart->tax_amount,
            'discount_amount' => $cart->discount_amount
        ];

        if ($request->has('guest') && $request->guest) {
            $orderData['session_id'] = $request->session_id;
        }

        $order = Order::create($orderData);

        return response()->json([
            'success' => true,
            'message' => 'Order created successfully',
            'order' => $order
        ], 201);
    }
} 
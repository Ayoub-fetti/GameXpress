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
            $cart = Cart::where('user_id', Auth::guard('sanctum')->id())->first();
        } else {
            $cart = Cart::where('session_id', $request->session_id)->first();
        }

        if (!$cart) {
            return response()->json(['message' => 'Cart not found'], 404);
        }

        if ($cart->total_amount <= 0) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $orderData = [
            'user_id' => $cart->user_id,
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
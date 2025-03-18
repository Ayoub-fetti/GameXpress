<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class CartController extends Controller
{

    public function index()
    {
        return response()->json(Product::all(),200);
    }

    public function addToCart(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
                'errors' => ['product_id' => ['The selected product does not exist.']]
            ], 404);
        }

        if ($request->quantity > $product->stock) {

        }

        if (Auth::check()) {
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
        } else {
            $cart = Cart::firstOrCreate(['session_id' => Session::getId()]);
        }
        
        $cartItem = CartItem::where('cart_id', $cart->id)
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            if (($cartItem->quantity + $request->quantity) > $product->stock) {
                return response()->json([
                    'message' => 'Not enough stock available',
                    'errors' => ['quantity' => ['The total quantity would exceed available stock.']]
                ], 400);
            }
            $cartItem->quantity += $request->quantity;
            $cartItem->total_price = $cartItem->quantity * $cartItem->unit_price;
            $cartItem->save();
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
                'total_price' => $request->quantity * $product->price,
            ]);
        }

        return response()->json([
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem,
        ], 201);
    }

    public function getCart(): JsonResponse
    {
        if (Auth::check()) {
            $cart = Cart::with('items.product')->where('user_id', Auth::id())->first();
        } else {
            $cart = Cart::with('items.product')->where('session_id', Session::getId())->first();
        }

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found',
            ], 404);
        }

        $cartTotals = \App\Helpers\CartHelper::getCartTotals($cart);

        return response()->json([
            'cart' => $cart,
            'items' => $cart->items,
            'totals' => $cartTotals
        ], 200);
    }

    public function updateCartItem(Request $request)
    {

    }


    public function removeCartItem(Request $request)
    {

    }
}

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
use Illuminate\Support\Str;
use App\Helpers\CartHelper;
use Laravel\Sanctum\Sanctum;

class CartController extends Controller
{
    private const TAX_RATE = 20;

    private function getOrCreateSessionId(Request $request)
    {
        $sessionId = $request->header('X-Session-Id');
        if (!$sessionId) {
            $sessionId = Str::random(32);
        }
        return $sessionId;
    }

    public function index()
    {
        return response()->json(Product::all(),200);
    }

    public function addToCartGuest(Request $request): JsonResponse
    {
        $request->validate([
            'product_id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ]);

        $product = Product::find($request->product_id);

        if (!$product) {
            return response()->json([
                'message' => 'Product not found',
                'errors' => ['product_id' => ['The selected product does not exist.']]
            ], 404);
        }

        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => 'Not enough stock available',
                'errors' => ['quantity' => ['The requested quantity exceeds available stock.']]
            ], 400);
        }

        if (Auth::check()) {
            $cart = Cart::firstOrCreate([
                'user_id' => Auth::id()
            ], [
                'tax_rate' => self::TAX_RATE
            ]);
        } else {
            $sessionId = $this->getOrCreateSessionId($request);
            $cart = Cart::firstOrCreate([
                'session_id' => $sessionId
            ], [
                'tax_rate' => self::TAX_RATE
            ]);
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
            CartHelper::updateCartItemTotal($cartItem);
        } else {
            $cartItem = CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $product->id,
                'quantity' => $request->quantity,
                'unit_price' => $product->price,
                'total_price' => $request->quantity * $product->price,
            ]);
        }

        CartHelper::calculateCartTotals($cart);

        $response = [
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem,
            'cart_totals' => CartHelper::getCartTotals($cart)
        ];

        if (!Auth::check()) {
            $response['session_id'] = $sessionId;
        }

        return response()->json($response, 201);
    }
    // *****
    public function getCart(Request $request): JsonResponse
    {
        if (Auth::guard('sanctum')->check()) {
            $cart = Cart::with('items.product')->where('user_id', Auth::guard('sanctum')->id())->first();
            // dd(Auth::guard('sanctum')->id());
        } else {
            $sessionId = $request->header('X-Session-Id');
            // dd($sessionId);
            if (!$sessionId) {
                return response()->json([
                    'message' => 'Session ID is required',
                ], 400);
            }
            $cart = Cart::with('items.product')->where('session_id', $sessionId)->first();
        }

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found',
            ], 404);
        }

        return response()->json([
            'cart' => $cart,
            'items' => $cart->items,
            'totals' => CartHelper::getCartTotals($cart)
        ], 200);
    }
    // ***** 
    public function applyPromoCode(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string'
        ]);

        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
        } else {
            $sessionId = $request->header('X-Session-Id');
            if (!$sessionId) {
                return response()->json([
                    'message' => 'Session ID is required'
                ], 400);
            }
            $cart = Cart::where('session_id', $sessionId)->first();
        }

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $result = CartHelper::applyPromoCode($cart, $request->code);

        return response()->json($result);
    }

    public function updateCartItem(Request $request): JsonResponse
    {
        $request->validate([
            'cart_item_id' => 'required|integer',
            'quantity' => 'required|integer|min:1'
        ]);

        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
        } else {
            $sessionId = $request->header('X-Session-Id');
            // if (!$sessionId) {
            //     return response()->json([
            //         'message' => 'Session ID is required'
            //     ], 400);
            // }
            $cart = Cart::where('session_id', $sessionId)->first();
        }

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $cartItem = CartItem::where('id', $request->cart_item_id)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        $product = Product::find($cartItem->product_id);
        if ($request->quantity > $product->stock) {
            return response()->json([
                'message' => 'Not enough stock available',
                'errors' => ['quantity' => ['The requested quantity exceeds available stock.']]
            ], 400);
        }

        $cartItem->quantity = $request->quantity;
        CartHelper::updateCartItemTotal($cartItem);
        CartHelper::calculateCartTotals($cart);

        return response()->json([
            'message' => 'Cart item updated successfully',
            'cart_item' => $cartItem,
            'totals' => CartHelper::getCartTotals($cart)
        ], 200);
    }

    public function removeCartItem($id, Request $request): JsonResponse
    {
        if (Auth::check()) {
            $cart = Cart::where('user_id', Auth::id())->first();
        } else {
            $sessionId = $request->header('X-Session-Id');
            // if (!$sessionId) {
            //     return response()->json([
            //         'message' => 'Session ID is required'
            //     ], 400);
            // }
            $cart = Cart::where('session_id', $sessionId)->first();
        }

        if (!$cart) {
            return response()->json([
                'message' => 'Cart not found'
            ], 404);
        }

        $cartItem = CartItem::where('id', $id)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$cartItem) {
            return response()->json([
                'message' => 'Cart item not found'
            ], 404);
        }

        $cartItem->delete();
        CartHelper::calculateCartTotals($cart);

        return response()->json([
            'message' => 'Cart item removed successfully',
            'totals' => CartHelper::getCartTotals($cart)
        ], 200);
    }
    public function addToCartClient(Request $request): JsonResponse{

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
            return response()->json([
                'message' => 'Not enough stock available',
                'errors' => ['quantity' => ['The requested quantity exceeds available stock.']]
            ], 400);
        }

        if (Auth::check()) {
            
            $cart = Cart::firstOrCreate([
                'user_id' => Auth::id()
            ], [
                'tax_rate' => self::TAX_RATE
            ]);
            
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($cartItem) {
                
                $cartItem->quantity += $request->quantity;
                CartHelper::updateCartItemTotal($cartItem);
            } else {
                
                $cartItem = CartItem::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'quantity' => $request->quantity,
                    'unit_price' => $product->price,
                    'total_price' => $request->quantity * $product->price,
                ]);
            }

            CartHelper::calculateCartTotals($cart);
        
            return response()->json([
                'message' => 'Product added to cart successfully you are login',
                'cart_item' => $cartItem,
                'cart_totals' => CartHelper::getCartTotals($cart)
            ], 201);
        }
        
        return response()->json([
            'message' => 'User not authenticated',
            'errors' => ['auth' => ['You must be logged in to add items to your cart.']]
        ], 401);
    }
    public function mergeCartAfterLogin(Request $request): JsonResponse
{
    if (!Auth::check()) {
        return response()->json([
            'message' => 'User not authenticated',
        ], 401);
    }

    $sessionId = $request->header('X-Session-Id');
    if (!$sessionId) {
        return response()->json([
            'message' => 'Session ID is required',
        ], 400);
    }

    $sessionCart = Cart::with('items.product')->where('session_id', $sessionId)->first();
    if (!$sessionCart) {
        return response()->json([
            'message' => 'Session cart not found',
        ], 404);
    }

    $userCart = Cart::firstOrCreate(['user_id' => Auth::id()]);

    foreach ($sessionCart->items as $sessionItem) {
        $userItem = CartItem::where('cart_id', $userCart->id)
                          ->where('product_id', $sessionItem->product_id)
                          ->first();

        if ($userItem) {
            $newQuantity = $userItem->quantity + $sessionItem->quantity;

            if ($newQuantity <= $sessionItem->product->stock) {
                $userItem->quantity = $newQuantity;
                $userItem->total_price = $newQuantity * $userItem->unit_price;
                $userItem->save();
            } else {
                $userItem->quantity = $sessionItem->product->stock;
                $userItem->total_price = $sessionItem->product->stock * $userItem->unit_price;
                $userItem->save();
            }

            $sessionItem->delete();
        } else {
            $sessionItem->cart_id = $userCart->id;
            $sessionItem->save();
        }
    }

    $sessionCart->delete();

    $updatedCart = Cart::with('items.product')->where('user_id', Auth::id())->first();
    // $cartTotals = \App\Helpers\CartHelper::getCartTotals($updatedCart);

    return response()->json([
        'message' => 'Cart merged successfully',
        'cart' => $updatedCart,
        // 'items' => $updatedCart->items,
        // 'totals' => $cartTotals
    ], 200);
}
public function mergeGuestCart($sessionId, $userId)
{

    $guestCart = Cart::where('session_id', $sessionId)->first();

    if (!$guestCart) {
        return response()->json([
            'message' => 'Panier invité non trouvé',
            'status' => 'error'
        ], 404);
    }

    $userCart = Cart::firstOrCreate(['user_id' => $userId]);

    $guestCartItems = CartItem::where('cart_id', $guestCart->id)->get();

    if ($guestCartItems->isEmpty()) {
        return response()->json([
            'message' => 'Panier invité vide',
            'status' => 'info'
        ], 200);
    }
    foreach ($guestCartItems as $guestItem) {
        $userItem = CartItem::where('cart_id', $userCart->id)
                           ->where('product_id', $guestItem->product_id)
                           ->first();

        $product = Product::find($guestItem->product_id);
        if (!$product) {
            continue;
        }
        if ($userItem) {
            $newQuantity = $userItem->quantity + $guestItem->quantity;
            // Vérifier le stock disponible
            if ($newQuantity > $product->stock) {
                $newQuantity = $product->stock; // Limiter à la quantité en stock
            }
            $userItem->quantity = $newQuantity;
            $userItem->total_price = $newQuantity * $userItem->unit_price;
            $userItem->save();
            $guestItem->delete();
        } else {
            $guestItem->cart_id = $userCart->id;
            $guestItem->save();
        }
    }
    $guestCart->delete();
    return response()->json([
        'message' => 'Panier fusionné avec succès',
        'status' => 'success'
    ], 200);
}


}

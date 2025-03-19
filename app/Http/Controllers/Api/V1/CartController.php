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

class CartController extends Controller
{
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

    // public function addToCart(Request $request): JsonResponse
    // {
    //     $request->validate([
    //         'product_id' => 'required|integer',
    //         'quantity' => 'required|integer|min:1',
    //     ]);

    //     $product = Product::find($request->product_id);

    //     if (!$product) {
    //         return response()->json([
    //             'message' => 'Product not found',
    //             'errors' => ['product_id' => ['The selected product does not exist.']]
    //         ], 404);
    //     }

    //     if ($request->quantity > $product->stock) {
    //         return response()->json([
    //             'message' => 'Not enough stock available',
    //             'errors' => ['quantity' => ['The requested quantity exceeds available stock.']]
    //         ], 400);
    //     }

    //     if (Auth::check()) {
    //         $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
    //     } else {
    //         $cart = Cart::firstOrCreate(['session_id' => Session::getId()]);
    //     }
    //     $cartItem = CartItem::where('cart_id', $cart->id)
    //         ->where('product_id', $product->id)
    //         ->first();

    //     if ($cartItem) {
    //         return response()->json([
    //             'message' => 'Product already in cart',
    //             'errors' => ['product_id' => ['The selected product is already in cart.']]
    //         ], 400);
    //     } else {
    //         $cartItem = CartItem::create([
    //             'cart_id' => $cart->id,
    //             'product_id' => $product->id,
    //             'quantity' => $request->quantity,
    //             'unit_price' => $product->price,
    //             'total_price' => $request->quantity * $product->price,
    //         ]);
    //     }

    //     return response()->json([
    //         'message' => 'Product added to cart successfully',
    //         'cart_item' => $cartItem,
    //     ], 201);
    // }
    public function addToCartGuest(Request $request): JsonResponse
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
            return response()->json([
                'message' => 'Not enough stock available',
                'errors' => ['quantity' => ['The requested quantity exceeds available stock.']]
            ], 400);
        }


            $sessionId = $this->getOrCreateSessionId($request);
            $cart = Cart::firstOrCreate(['session_id' => $sessionId]);


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

        $response = [
            'message' => 'Product added to cart successfully',
            'cart_item' => $cartItem,
        ];

        if (!Auth::check()) {
            $response['session_id'] = $sessionId;
        }

        return response()->json($response, 201);
    }

    public function getCart(Request $request): JsonResponse
    {
        if (Auth::check()) {
            $cart = Cart::with('items.product')->where('user_id', Auth::id())->first();
        } else {
            $sessionId = $request->header('X-Session-Id');
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
            
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            
          
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            if ($cartItem) {
                
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
                'message' => 'Product added to cart successfully you are login',
                'cart_item' => $cartItem,
            ], 201);
        } else {
            return response()->json([
                'message' => 'User not authenticated',
                'errors' => ['auth' => ['You must be logged in to add items to your cart.']]
            ], 401);
        }
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
public function mergeGuestCart($sessionId, $user_id){
    $cartItemsGuest = Cart::where('session_id', $sessionId)->get();
    $cartItemsUser = Cart::where('user_id', $user_id)->get();
    foreach ($cartItemsGuest as $cartItemGuest) {
        //verifier si le produit existe dans le panier de l'utilisateur
        $cartItemUser = $cartItemsUser->where('product_id', $cartItemGuest->product_id)->first();
        if ($cartItemUser) {
            $cartItemUser->quantity += $cartItemGuest->quantity;
            if($cartItemUser->quantity > Product::find($cartItemUser->product_id)->stock){
                return response()->json(['message' => 'stock insuffisant', 'status' => 'error'], 400);
            }
            $cartItemUser->price = $cartItemUser->quantity * Product::find($cartItemUser->product_id)->price;
            $cartItemUser->save();
            $cartItemGuest->delete();
            $cartItemGuest->user_id = $user_id;
            $cartItemGuest->session_id = null;
            $cartItemGuest->save();

        } else {
            $cartItemGuest->user_id = $user_id;
            $cartItemGuest->session_id = null;
            $cartItemGuest->save();
        }
    }
    return response()->json(['message' => 'panier fusionné avec succès', 'status' => 'success'], 200);

}
}
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

        /**
         * 
         * @O
         * @OA\Get(
         *     path="/products",
         *     summary="Retrieve all products",
         *     tags={"Cart"},
         *     @OA\Response(
         *         response=200,
         *         description="Successful operation",
         *     ),
         *     @OA\Response(
         *         response=500,
         *         description="Server error"
         *     )
         * )
         */

         public function index()
         {
             $products = Product::with('images')->get();
             return response()->json($products, 200);
         }

        /**
         * @OA\Post(
         *     path="/cart/guest/add",
         *     summary="Add a product to the guest cart",
         *     tags={"Cart"},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="product_id", type="integer", example=1, description="ID of the product to add"),
         *             @OA\Property(property="quantity", type="integer", example=2, description="Quantity of the product to add")
         *         )
         *     ),
         *     @OA\Response(
         *         response=201,
         *         description="Product added to cart successfully",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="Product added to cart successfully"),
         *             @OA\Property(property="cart_item", type="object", description="Details of the added cart item"),
         *             @OA\Property(property="cart_totals", type="object", description="Cart totals after the addition"),
         *             @OA\Property(property="session_id", type="string", example="random-session-id", description="Session ID for guest users (if applicable)")
         *         )
         *     ),
         *     @OA\Response(
         *         response=400,
         *         description="Invalid input or insufficient stock",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="Not enough stock available"),
         *             @OA\Property(property="errors", type="object", description="Details of the validation errors")
         *         )
         *     ),
         *     @OA\Response(
         *         response=404,
         *         description="Product not found",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="Product not found"),
         *             @OA\Property(property="errors", type="object", description="Details of the error")
         *         )
         *     )
         * )
 */



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

    /**
     * @OA\Get(
     *     path="/Show",
     *     summary="Retrieve the current user's cart",
     *     tags={"Cart"},
     *     @OA\Parameter(
     *         name="X-Session-Id",
     *         in="header",
     *         required=false,
     *         description="Session ID for guest users",
     *         @OA\Schema(type="string", example="random-session-id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="cart", type="object", description="Details of the cart"),
     *             @OA\Property(property="items", type="array", @OA\Items(type="object"), description="List of cart items"),
     *             @OA\Property(property="totals", type="object", description="Cart totals")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Session ID is required for guest users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Session ID is required")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cart not found")
     *         )
     *     )
     * )
     */
    public function getCart(Request $request): JsonResponse
    {
        if (Auth::guard('sanctum')->check()) {
            $cart = Cart::with('items.product')->where('user_id', Auth::guard('sanctum')->id())->first();
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

        return response()->json([
            'cart' => $cart,
            'items' => $cart->items,
            'totals' => CartHelper::getCartTotals($cart)
        ], 200);
    }
    /**
     * @OA\Post(
     *     path="/promo_code",
     *     summary="Apply a promo code to the cart",
     *     tags={"Cart"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="code", type="string", example="PROMO2025", description="Promo code to apply")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Promo code applied successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Promo code applied successfully"),
     *             @OA\Property(property="discount", type="number", format="float", example=10.5, description="Discount amount applied"),
     *             @OA\Property(property="cart_totals", type="object", description="Updated cart totals after applying the promo code")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid promo code or session ID",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Invalid promo code"),
     *             @OA\Property(property="errors", type="object", description="Details of the validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cart not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Put(
     *     path="item/update",
     *     summary="Update the quantity of a cart item",
     *     tags={"Cart"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="cart_item_id", type="integer", example=1, description="ID of the cart item to update"),
     *             @OA\Property(property="quantity", type="integer", example=3, description="New quantity for the cart item")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart item updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cart item updated successfully"),
     *             @OA\Property(property="cart_item", type="object", description="Details of the updated cart item"),
     *             @OA\Property(property="totals", type="object", description="Updated cart totals")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Invalid input or insufficient stock",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Not enough stock available"),
     *             @OA\Property(property="errors", type="object", description="Details of the validation errors")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart or cart item not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cart or cart item not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */
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
    /**
     * @OA\Delete(
     *     path="/item/remove/{id}",
     *     summary="Remove an item from the cart",
     *     tags={"Cart"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="ID of the cart item to remove",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="X-Session-Id",
     *         in="header",
     *         required=false,
     *         description="Session ID for guest users",
     *         @OA\Schema(type="string", example="random-session-id")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cart item removed successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cart item removed successfully"),
     *             @OA\Property(property="totals", type="object", description="Updated cart totals")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cart or cart item not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="Cart or cart item not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="User not authenticated",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="message", type="string", example="User not authenticated")
     *         )
     *     )
     * )
     */

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

           /**
         * @OA\Post(
         *     path="/cart/client/add",
         *     summary="Add a product to the client cart",
         *     tags={"Cart"},
         *     @OA\RequestBody(
         *         required=true,
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="product_id", type="integer", example=1, description="ID of the product to add"),
         *             @OA\Property(property="quantity", type="integer", example=2, description="Quantity of the product to add")
         *         )
         *     ),
         *     @OA\Response(
         *         response=201,
         *         description="Product added to cart successfully",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="Product added to cart successfully"),
         *             @OA\Property(property="cart_item", type="object", description="Details of the added cart item"),
         *             @OA\Property(property="cart_totals", type="object", description="Cart totals after the addition"),
         *         )
         *     ),
         *     @OA\Response(
         *         response=400,
         *         description="Invalid input or insufficient stock",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="Not enough stock available"),
         *             @OA\Property(property="errors", type="object", description="Details of the validation errors")
         *         )
         *     ),
         *     @OA\Response(
         *         response=404,
         *         description="Product not found",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="Product not found"),
         *             @OA\Property(property="errors", type="object", description="Details of the error")
         *         )
         *     ),
         *     @OA\Response(
         *         response=401,
         *         description="User not authenticated",
         *         @OA\JsonContent(
         *             type="object",
         *             @OA\Property(property="message", type="string", example="User not authenticated"),
         *             @OA\Property(property="errors", type="object", description="Details of the error")
         *         )
         *     )
         * )
 */
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

        $userCart = Cart::firstOrCreate(
            ['user_id' => $userId],
            [
                'tax_rate' => $guestCart->tax_rate ?? self::TAX_RATE,
                'subtotal' => 0,
                'tax_amount' => 0,
                'discount_amount' => 0,
                'total_amount' => 0,
                'expires_at' => now()->addDays(7)
            ]
        );

        $guestCartItems = CartItem::where('cart_id', $guestCart->id)->get();

        if ($guestCartItems->isEmpty()) {
            return response()->json([
                'message' => 'Panier invité vide',
                'status' => 'info'
            ], 200);
        }

        if ($guestCart->discount_amount > 0) {
            $userCart->discount_amount = $guestCart->discount_amount;
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
                
                if ($newQuantity > $product->stock) {
                    $newQuantity = $product->stock;
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

        CartHelper::calculateCartTotals($userCart);
        
        $userCart->expires_at = now()->addDays(7);
        $userCart->save();
        
        $guestCart->delete();

        return response()->json([
            'message' => 'Panier fusionné avec succès',
            'status' => 'success',
            'cart' => $userCart,
            'items' => $userCart->items,
            'totals' => CartHelper::getCartTotals($userCart)
        ], 200);
    }


}

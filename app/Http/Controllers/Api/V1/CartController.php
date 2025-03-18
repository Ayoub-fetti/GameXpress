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

    public function getCart()
    {
    //
    }

    public function updateCartItem(Request $request)
    {

    }


    public function removeCartItem(Request $request)
    {

    }

    private function getOrCreateSessionId(Request $request)
    {
        // Check if session_id is provided in the request
        if ($request->has('session_id')) {
            return $request->session_id;
        }
        
        // If no session_id provided, use the current session ID or create a new one
        if (!Session::has('cart_session_id')) {
            Session::put('cart_session_id', Session::getId());
        }
        
        return Session::get('cart_session_id');
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
            // Utilisateur authentifié - stocker dans la base de données
            $cart = Cart::firstOrCreate(['user_id' => Auth::id()]);
            
            // Vérifier si le produit est déjà dans le panier
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            if ($cartItem) {
                // Mettre à jour la quantité si le produit existe déjà
                $cartItem->quantity += $request->quantity;
                $cartItem->total_price = $cartItem->quantity * $cartItem->unit_price;
                $cartItem->save();
            } else {
                // Ajouter un nouvel article au panier
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
            // Utilisateur non authentifié - stocker dans la session
            $cart = Cart::firstOrCreate(['session_id' => Session::getId()]);
            
            // Vérifier si le produit est déjà dans le panier
            $cartItem = CartItem::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();
            
            if ($cartItem) {
                // Mettre à jour la quantité si le produit existe déjà
                $cartItem->quantity += $request->quantity;
                $cartItem->total_price = $cartItem->quantity * $cartItem->unit_price;
                $cartItem->save();
            } else {
                // Ajouter un nouvel article au panier
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
    }
}

<?php 
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{




    public function createSession()
    {
        // dd(env('STRIPE_SECRET'));
        // Configurez votre clé secrète Stripe
        
        Stripe::setApiKey('sk_test_51R41RuHIFvEer26VQ1G7WXYw6e7hszFa6uu15IPwCWK9M3i2w0EP68Z4ATWbFLBYk38R8IsRhbLB7XjWM1hKvzZb00kIn93CPd'); 

        

    

        // Récupérer la commande
        $order = Order::where('user_id', Auth::id()) // Ensure the order belongs to the authenticated user
        ->first();

        // dd($order);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $lineItems = [];
        foreach ($order->products as $product) {

            $unitPriceWithTax = $product['unit_price'] + ($product['unit_price'] * ($product['tax_rate'] / 100));
            
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'MAD',
                    'product_data' => [
                        'name' => $product['product_name'],
                        'description' => "Quantity: {$product['quantity']} - Price (inc. VAT): {$unitPriceWithTax} MAD"
                    ],
                    'unit_amount' => round($unitPriceWithTax * 100), 
                ],
                'quantity' => $product['quantity'],
            ];
        }

        // Créer une session Stripe
        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => 'http://127.0.0.1:8000/api/cart/success',
                'cancel_url' => 'http://127.0.0.1:8000/api/cart/cancel',
                'automatic_tax' => [
                    'enabled' => false,
                ],
            ]);

            return response()->json([
                'id' => $session->id,
                'url' => $session->url,
            ]);
        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
    }
}
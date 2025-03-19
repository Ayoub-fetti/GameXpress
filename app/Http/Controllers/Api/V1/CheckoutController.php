<?php 
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CheckoutController extends Controller
{




    public function createSession()
    {
        
        // Configurez votre clé secrète Stripe
        
        Stripe::setApiKey(env('STRIPE_SECRET')); 

    

        // Récupérer la commande
        $order = Order::where('user_id', Auth::id()) // Ensure the order belongs to the authenticated user
        ->first();

        // dd($order);

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }
        // Créer les items Stripe à partir de la commande
        $lineItems = [
            [
                'price_data' => [
                    'currency' => 'MAD',
                    'product_data' => [
                        'name' => 'Order #' . $order->id,
                    ],
                    'unit_amount' => $order->total_price * 100, // Convertir en centimes
                ],
                'quantity' => 1,
            ]
        ];

        // Créer une session Stripe

            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => env('APP_URL') . '/success',
                'cancel_url' => env('APP_URL') . '/cancel',
            ]);
            // Retourner l'ID de session et l'URL de redirection
            return response()->json([
                'id' => $session->id,
                'url' => $session->url, // URL de redirection vers Stripe Checkout
            ]);
    }
}
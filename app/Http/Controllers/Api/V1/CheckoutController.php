<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{




    public function createSession()
    {

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
                'success_url' => 'http://127.0.0.1:8000/api/cart/success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id,
                'cancel_url' => 'http://127.0.0.1:8000/api/cart/cancel?order_id=' . $order->id,
            ]);
            // dd( $session );

                    // Créer un enregistrement de paiement
            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::id(),
                'stripe_session_id' => $session->id,
                'amount' => $order->total_price,
                'currency' => 'MAD',
                'status' => 'pending',
                'payment_details' => [
                    'session_url' => $session->url,
                    'created_at' => now()->toISOString()
                ]
            ]);
            // $order->update(['status' => 'pending']);

            return response()->json([
                'id' => $session->id,
                'url' => $session->url,
            ]);
    }

    public function success(Request $request)
    {

        $sessionId = $request->query('session_id');
        $orderId = $request->query('order_id');

        if (!$sessionId || !$orderId) {
            return response()->json(['message' => 'Session ID and Order ID are required'], 400);
        }

        Stripe::setApiKey('sk_test_51R41RuHIFvEer26VQ1G7WXYw6e7hszFa6uu15IPwCWK9M3i2w0EP68Z4ATWbFLBYk38R8IsRhbLB7XjWM1hKvzZb00kIn93CPd');

        try {
            $session = Session::retrieve($sessionId);

            // Si le statut de paiement est "paid", mettre à jour la commande
            // Retrieve the order and check if it has pending status
            $order = Order::find($orderId);

            if ($order && $order->status === 'pending') {
                // Retrouver le paiement
                $payment = Payment::where('stripe_session_id', $sessionId)->first();

                if (!$payment) {
                    // Si le paiement n'est pas trouvé, créons-le
                    $payment = Payment::create([
                        'order_id' => $orderId,
                        'user_id' => Auth::id(),
                        'stripe_session_id' => $sessionId,
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'payment_method' => 'stripe',
                        'amount' => $session->amount_total / 100, // Convertir de centimes
                        'currency' => $session->currency,
                        'status' => 'completed',
                        'payment_details' => [
                            'payment_status' => $session->payment_status,
                            'completed_at' => now()->toISOString()
                        ],
                        'paid_at' => now()
                    ]);
                } else {
                    // Mettre à jour le paiement existant
                    $payment->update([
                        'status' => 'completed',
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'payment_details' => array_merge($payment->payment_details ?? [], [
                            'payment_status' => $session->payment_status,
                            'completed_at' => now()->toISOString()
                        ]),
                        'paid_at' => now()
                    ]);
                }

                // Mettre à jour la commande
                $order->update(['status' => 'shipped']);

                return response()->json([
                    'message' => 'Paiement réussi',
                    'order_id' => $orderId,
                    'order_status' => 'shipped'
                ]);
            }

            return response()->json([
                'message' => 'Le statut du paiement est: ' . $session->payment_status
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la vérification du paiement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function cancel(Request $request)
    {
        $orderId = $request->query('order_id');

        if ($orderId) {
            // Mettre à jour le statut de la commande
            $order = Order::find($orderId);
            if ($order) {
                $order->update(['status' => 'payment_cancelled']);

                // Mettre à jour le paiement s'il existe
                $payment = Payment::where('order_id', $orderId)
                    ->where('status', 'pending')
                    ->latest()
                    ->first();

                if ($payment) {
                    $payment->update([
                        'status' => 'cancelled',
                        'payment_details' => array_merge($payment->payment_details ?? [], [
                            'cancelled_at' => now()->toISOString()
                        ])
                    ]);
                }
            }
        }

        return response()->json([
            'message' => 'Paiement annulé',
            'order_id' => $orderId
        ]);
    }
}

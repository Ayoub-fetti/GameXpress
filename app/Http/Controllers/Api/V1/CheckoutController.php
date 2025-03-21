<?php
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Container\Attributes\Log;

class CheckoutController extends Controller
{




    public function createSession(Request $request)
    {
        if (!Auth::guard('sanctum')->check()) {
            return response()->json([
                'message' => 'Unauthorized'
            ], 401);
        }

        Stripe::setApiKey('sk_test_51R41RuHIFvEer26VQ1G7WXYw6e7hszFa6uu15IPwCWK9M3i2w0EP68Z4ATWbFLBYk38R8IsRhbLB7XjWM1hKvzZb00kIn93CPd');

        $order = Order::where('user_id', Auth::guard('sanctum')->id())
            ->where('status', 'pending')
            ->latest()
            ->first();

        if (!$order) {
            return response()->json([
                'message' => 'Order not found'
            ], 404);
        }

        $lineItems = [];
        
        foreach ($order->products as $product) {
            $pricePerUnit = floatval($order->total_price) / array_sum(array_column($order->products, 'quantity'));
            
            $lineItems[] = [
                'price_data' => [
                    'currency' => 'MAD',
                    'product_data' => [
                        'name' => $product['product_name'],
                        'description' => "Prix total: {$order->total_price} MAD - TVA incluse: {$order->tax_amount} MAD - Remise: {$order->discount_amount} MAD"
                    ],
                    'unit_amount' => round($pricePerUnit * 100),
                ],
                'quantity' => $product['quantity'],
            ];
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => 'http://127.0.0.1:8000/api/cart/success?session_id={CHECKOUT_SESSION_ID}&order_id=' . $order->id,
                'cancel_url' => 'http://127.0.0.1:8000/api/cart/cancel?order_id=' . $order->id,
            ]);

            $payment = Payment::create([
                'order_id' => $order->id,
                'user_id' => Auth::guard('sanctum')->id(),
                'stripe_session_id' => $session->id,
                'amount' => $order->total_price,
                'currency' => 'MAD',
                'status' => 'pending',
                'payment_details' => [
                    'session_url' => $session->url,
                    'created_at' => now()->toISOString(),
                    'products_count' => count($lineItems),
                    'subtotal' => $order->subtotal,
                    'discount_amount' => $order->discount_amount,
                    'tax_amount' => $order->tax_amount,
                    'final_price' => $order->total_price
                ]
            ]);

            return response()->json([
                'id' => $session->id,
                'url' => $session->url,
                'products_count' => count($lineItems),
                'amount' => $order->total_price
            ]);

        } catch (\Stripe\Exception\ApiErrorException $e) {
            return response()->json([
                'error' => $e->getMessage()
            ], 400);
        }
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

            $order = Order::find($orderId);

            if ($order && $order->status === 'pending') {
                $payment = Payment::where('stripe_session_id', $sessionId)->first();

                if (!$payment) {
                    // Si le paiement n'est pas trouvé, créons-le
                    $payment = Payment::create([
                        'order_id' => $orderId,
                        'user_id' => Auth::id(),
                        'stripe_session_id' => $sessionId,
                        'stripe_payment_intent_id' => $session->payment_intent,
                        'payment_method' => 'stripe',
                        'amount' => $session->amount_total / 100, 
                        'currency' => $session->currency,
                        'status' => 'completed',
                        'payment_details' => [
                            'payment_status' => $session->payment_status,
                            'completed_at' => now()->toISOString()
                        ],
                        'paid_at' => now()
                    ]);
                } else {
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

                $order->update(['status' => 'processing']);


                if ($order && $order->user_id) {
                    // Use the order's user_id instead of relying on Auth::id()
                    Cart::where('user_id', $order->user_id)->delete();

                    return response()->json([
                        'message' => 'Paiement reussi',
                        'order_id' => $orderId,
                        'order_status' => 'processing',
                        'cart_deleted' => true
                    ]);
                } else {
                    // Log that we couldn't find a user ID to delete the cart
                    return response()->json('Cannot delete cart: No user ID available', [
                        'order_id' => $orderId,
                        'auth_id' => Auth::id(),
                        'order_user_id' => $order->user_id ?? null
                    ]);

                    return response()->json([
                        'message' => 'Paiement reussi',
                        'order_id' => $orderId,
                        'order_status' => 'processing'
                    ]);
                }
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

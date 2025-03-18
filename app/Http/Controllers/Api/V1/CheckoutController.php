<?php 
namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class CheckoutController extends Controller
{



    public function createSession(Request $request)
{
    // Configurez votre clé secrète Stripe
    Stripe::setApiKey(env('STRIPE_SECRET'));

    // Récupérer le panier
    $cartController = new CartController();
    $cartResponse = $cartController->getCart($request);
    $cartData = $cartResponse->getData();

    if ($cartResponse->getStatusCode() !== 200) {
        return response()->json([
            'message' => 'Unable to retrieve cart: ' . $cartData->message
        ], $cartResponse->getStatusCode());
    }

    $cart = $cartData->cart;

    // Appliquer le code promo si fourni
    if ($request->has('promo_code')) {
        $promoResponse = $cartController->applyPromoCode($request);
        $promoData = $promoResponse->getData();

        if (!$promoData->success) {
            return response()->json([
                'message' => $promoData->message
            ], 400);
        }

        $cart = $promoData->cart_totals;
    }

    // Créer les items Stripe à partir des articles du panier
    $lineItems = [];
    foreach ($cart->items as $item) {
        $lineItems[] = [
            'price_data' => [
                'currency' => 'MAD',
                'product_data' => [
                    'name' => $item->product->name,
                ],
                'unit_amount' => $item->unit_price * 100, // Convertir en centimes
            ],
            'quantity' => $item->quantity,
        ];
    }

    // Ajouter une ligne pour la remise si applicable
    if ($cart->discount_amount > 0) {
        $lineItems[] = [
            'price_data' => [
                'currency' => 'MAD',
                'product_data' => [
                    'name' => 'Discount',
                ],
                'unit_amount' => -$cart->discount_amount * 100, // Remise en centimes
            ],
            'quantity' => 1,
        ];
    }

    // Créer une session Stripe
    $session = Session::create([
        'payment_method_types' => ['card'],
        'line_items' => $lineItems,
        'mode' => 'payment',
        'success_url' => env('APP_URL') . '/success',
        'cancel_url' => env('APP_URL') . '/cancel',
    ]);

    return response()->json(['id' => $session->id]);
}
}
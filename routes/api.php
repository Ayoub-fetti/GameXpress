<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Mail;
use App\Http\Controllers\Api\V1\Admin\DashbordController;
use App\Http\Controllers\Api\V1\Admin\UserAuthController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\CheckoutController;

Route::post('/v1/admin/register', [UserAuthController::class, 'register']);
Route::post('/v1/admin/login', [UserAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/admin/logout', [UserAuthController::class, 'logout']);


    Route::middleware('role:super_admin')->group(function () {
        Route::post('/v1/admin/users/{userId}/assign-roles-permissions', [UserAuthController::class, 'assignRolesAndPermissions']);
        Route::get('/v1/admin/transactions', [DashbordController::class, 'listTransactions']);
        Route::post('/v1/admin/orders/{orderId}', [DashbordController::class, 'updateOrderStatus']);
    });

    Route::middleware('role:super_admin|user_manager|product_manager')->group(function () {
        Route::get('/v1/admin/dashboard', [DashbordController::class, 'index'])->name('admin.dashboard');
    });


    Route::middleware('role:user_manager|super_admin')->group(function () {
        Route::get('/v1/admin/users', [UserController::class, 'index']);
        Route::post('/v1/admin/users', [UserController::class, 'store']);
        Route::get('/v1/admin/users/{id}', [UserController::class, 'show']);
        Route::put('/v1/admin/users/{id}', [UserController::class, 'update']);
        Route::delete('/v1/admin/users/{id}', [UserController::class, 'destroy']);
    });
    Route::middleware('role:product_manager|super_admin')->prefix('v1/admin')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
    });
});

Route::prefix('cart')->group(function () {
    Route::get('/products', [CartController::class, 'index']);
    // Route::post('/add', [CartController::class, 'addToCart']);
    Route::get('/show', [CartController::class, 'getCart']);
    Route::post('/item/update', [CartController::class, 'updateCartItem']);
    Route::delete('/item/remove/{id}', [CartController::class, 'removeCartItem']);
    Route::post('/guest/add', [CartController::class, 'addToCartGuest']);


    Route::middleware('auth:sanctum')->group(function () {
        // Route::get('/show', [CartController::class, 'getCart']);
        Route::post('/promo_code', [CartController::class, 'applyPromoCode']);
        Route::post('/client/add', [CartController::class, 'addToCartClient']);
        Route::post('/cart/merge', [CartController::class, 'mergeCartAfterLogin']);
        Route::post('/sentorder', [OrderController::class, 'createFromCart']);
        Route::post('/payments', [CheckoutController::class, 'createSession']);
    });
});

// Routes de paiement
Route::get('/cart/success', [CheckoutController::class, 'success'])->name('payment.success');
Route::get('/cart/cancel', [CheckoutController::class, 'cancel'])->name('payment.cancel');

Route::prefix('orders')->group(function () {
    Route::post('/sentorder', [OrderController::class, 'createFromCart']);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please login'], 401);
})->name('login');


Route::get('/test-email', function() {
    // Create a mock Order object
    $order = new \App\Models\Order();
    $order->id = 123;
    $order->subtotal = 150;
    $order->tax_amount = 15;
    $order->discount_amount = 0;
    $order->total_price = 165;
    $order->status = 'processing';
    $order->created_at = now();
    
    // Create a mock user if needed in your template
    $user = new \App\Models\User();
    $user->name = 'Test User';
    $user->email = 'ayouboumha06@gmail.com';
    $order->user = $user;
    
    // Mock product data that matches your template's expectations
    $order->products = [
        [
            'product_name' => 'Test Product 1',
            'quantity' => 2,
            'price' => 55, // Make sure this key exists since your template uses it
        ],
        [
            'product_name' => 'Test Product 2',
            'quantity' => 1,
            'price' => 40,
        ],
    ];
    
    // Create a mock Payment object
    $payment = new \App\Models\Payment();
    $payment->amount = 165;
    $payment->status = 'completed';
    
    try {
        Mail::to('ayouboumha06@gmail.com')->send(new \App\Mail\OrderConfirmation($order, $payment));
        return 'Email sent!';
    } catch (\Exception $e) {
        return 'Error: ' . $e->getMessage() . ' in ' . $e->getFile() . ' line ' . $e->getLine();
    }
});
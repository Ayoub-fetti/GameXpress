<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\DashbordController;
use App\Http\Controllers\Api\V1\Admin\UserAuthController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\UserController;
use App\Http\Controllers\Api\V1\Admin\RoleController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\CheckoutController;

Route::get('v1/admin/products/{product}', [ProductController::class, 'show']);
Route::get('v1/admin/categories/{id}', [CategoryController::class, 'show']);


Route::get('/user', function (Request $request) {
    return response()->json([
        'user' => $request->user(),
        'roles' => $request->user()->getRoleNames()->toArray(),
        'permissions' => $request->user()->getAllPermissions()->pluck('name')->toArray()
    ]);
})->middleware('auth:sanctum');

Route::post('/register', [UserAuthController::class, 'register'])->name('register');
Route::post('/login', [UserAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserAuthController::class, 'logout']);
    
    
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
        Route::apiResource('/products', ProductController::class)->except(['show']);
        Route::apiResource('/categories', CategoryController::class)->except(['show']);
    });
});

Route::prefix('cart')->group(function () {
    Route::get('/products', [CartController::class, 'index']);
    Route::get('/show', [CartController::class, 'getCart']);
    Route::post('/item/update', [CartController::class, 'updateCartItem']);
    Route::delete('/item/remove/{id}', [CartController::class, 'removeCartItem']);
    Route::post('/guest/add', [CartController::class, 'addToCartGuest']);


    Route::middleware('auth:sanctum')->group(function () {
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


Route::get('data', [CategoryController::class , 'data'])->middleware('auth:sanctum');


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

Route::post('/v1/admin/register', [UserAuthController::class, 'register']);
Route::post('/v1/admin/login', [UserAuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/v1/admin/logout', [UserAuthController::class, 'logout']);


    Route::middleware('role:super_admin')->group(function () {
        Route::post('/v1/admin/users/{userId}/assign-roles-permissions', [UserAuthController::class, 'assignRolesAndPermissions']);
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
    Route::get('/', [CartController::class, 'index']);
    // Route::post('/add', [CartController::class, 'addToCart']);
    Route::get('/show', [CartController::class, 'getCart']);
    Route::post('/update', [CartController::class, 'updateCartItem']);
    Route::delete('remove-item/{id}', [CartController::class, 'removeCartItem']);
    Route::post('/add', [CartController::class, 'addToCartGuest']);
    
    
    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/apply-promo', [CartController::class, 'applyPromoCode']);
        Route::post('/add-client', [CartController::class, 'addToCartClient']);
        Route::post('/cart/merge', [CartController::class, 'mergeCartAfterLogin']);
        Route::post('/sentorder', [OrderController::class, 'createFromCart']);
        Route::post('/payments', [CheckoutController::class, 'createSession']);
    });
});


Route::prefix('orders')->group(function () {
    Route::post('/sentorder', [OrderController::class, 'createFromCart']); 
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please login'], 401);
})->name('login');
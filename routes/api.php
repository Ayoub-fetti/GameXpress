<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\DashbordController;
use App\Http\Controllers\Api\V1\Admin\UserAuthController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;
use App\Http\Controllers\Api\V1\Admin\UserController;

Route::post('/v1/admin/register', [UserAuthController::class, 'register']);
Route::post('/v1/admin/login', [UserAuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v1/admin/dashboard', [DashbordController::class, 'index'])->name('admin.dashboard');
    Route::post('/v1/admin/logout', [UserAuthController::class, 'logout']);
});

Route::middleware('role:super-admin|user-manager')->group(function () {
    Route::get('/v1/admin/users', [UserController::class, 'index']);
    Route::post('/v1/admin/users', [UserController::class, 'store']);
    Route::get('/v1/admin/users/{id}', [UserController::class, 'show']);
    Route::put('/v1/admin/users/{id}', [UserController::class, 'update']);
    Route::delete('/v1/admin/users/{id}', [UserController::class, 'destroy']);
});

    Route::middleware('role:super-admin|product-manager')->prefix('v1/admin')->group(function () {
    // Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
        Route::apiResource('products', ProductController::class);
        Route::apiResource('categories', CategoryController::class);
    });

Route::get('/login', function () {
    return response()->json(['message' => 'Please login'], 401);
})->name('login');
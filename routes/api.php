<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\Admin\DashbordController;
use App\Http\Controllers\Api\V1\Admin\UserAuthController;
use App\Http\Controllers\Api\V1\Admin\ProductController;
use App\Http\Controllers\Api\V1\Admin\CategoryController;

Route::post('/v1/admin/register', [UserAuthController::class, 'register']);
Route::post('/v1/admin/login', [UserAuthController::class, 'login']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v1/admin/dashboard', [DashbordController::class, 'index'])->name('admin.dashboard');
    Route::post('/v1/admin/logout', [UserAuthController::class, 'logout']);
});

Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('products', ProductController::class);
});
Route::middleware(['auth:sanctum'])->prefix('v1/admin')->group(function () {
    Route::apiResource('categories', CategoryController::class);
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please login'], 401);
})->name('login');

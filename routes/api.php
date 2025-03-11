<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserAuthController;
use App\Http\Controllers\DashbordController;

Route::post('/v1/admin/register', [UserAuthController::class, 'register']);
Route::post('/v1/admin/login', [UserAuthController::class, 'login']);
Route::post('/v1/admin/logout', [UserAuthController::class, 'logout']);


Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/v1/admin/dashboard', [DashbordController::class, 'index'])->name('admin.dashboard');
});

Route::get('/login', function () {
    return response()->json(['message' => 'Please login'], 401);
})->name('login');

Route::get('/user', function (Request $request) {
    return ['message' => 'Hello world'];
});

Route::get('/v1/status', function (Request $request) {
    return response()->json(['status' => 'OK']);
});
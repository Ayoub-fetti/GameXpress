<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdminAuthController;

Route::post('/v1/admin/register', [AdminAuthController::class, 'register']);
Route::post('/v1/admin/login', [AdminAuthController::class, 'login']);
Route::post('/v1/admin/logout', [AdminAuthController::class, 'logout']);

Route::middleware('auth:sanctum')->get('/v1/admin/dashboard', function (Request $request) {
    return response()->json(['message' => 'Dashboard data']);
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
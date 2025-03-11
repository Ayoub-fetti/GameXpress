<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return ['message' => 'Hello world'];
});

Route::get('/v1/status', function (Request $request) {
    return response()->json(['status' => 'OK']);
});
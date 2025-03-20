<?php

use Illuminate\Support\Facades\Route;

Route::get('/success', function () {
    return view('welcome');
});
Route::get('/cancel', function () {
    return view('welcome');
});





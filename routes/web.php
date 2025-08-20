<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-payment/{phoneNumber}/{template}', [ApiController::class, 'testPayment']);
Route::get('/test-payment', [ApiController::class, 'testNotification']);

Route::get('/about', function () {
    return view('about');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/terms-and-conditions', function () {
    return view('terms-and-condition');
});

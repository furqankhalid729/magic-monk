<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\RazorPaymentController;
Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-payment/{phoneNumber}/{template}', [ApiController::class, 'testPayment']);
Route::get('/test-discount', [ApiController::class, 'testNotification']);
Route::get('/create-payment-qr', [RazorPaymentController::class, 'createQr']);
Route::get('/test-razorpay', [RazorPaymentController::class, 'createQr']);


Route::get('/about', function () {
    return view('about');
});

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/terms-and-conditions', function () {
    return view('terms-and-condition');
});

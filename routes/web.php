<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\RazorPaymentController;
use App\Http\Controllers\SignUpController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/test-payment/{phoneNumber}/{template}', [ApiController::class, 'testPayment']);
Route::get('/test-discount', [ApiController::class, 'testNotification']);
Route::get('/create-payment-qr', [RazorPaymentController::class, 'createQr']);
Route::get('/test-razorpay', [RazorPaymentController::class, 'testConnection']);
Route::get('/test-subscription', [RazorPaymentController::class, 'testSubscription']);
Route::get('/about', function () {
    return view('about');
});

Route::get('sign-up', [SignUpController::class, 'create'])->name('sign-up');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/terms-and-conditions', function () {
    return view('terms-and-condition');
});


Route::get('/refund-and-cancellation', function () {
    return view('refund');
});

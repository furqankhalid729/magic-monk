<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\RazorPaymentController;
use App\Http\Controllers\SignUpController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\User\DashboardController;

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

Route::get('sign-up', [SignUpController::class, 'create'])->name('login');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard')->middleware('auth');
Route::post('/user-info/store', [DashboardController::class, 'store'])->name('user.info.store')->middleware('auth');
Route::post('/user-info/update-field', [DashboardController::class, 'updateField'])->name('user.info.update-field')->middleware('auth');
// OAuth Routes
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('auth.google');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('auth.google.callback');

Route::get('/auth/instagram', [AuthController::class, 'redirectToInstagram'])->name('auth.instagram');
Route::get('/auth/instagram/callback', [AuthController::class, 'handleInstagramCallback'])->name('auth.instagram.callback');

Route::get('/privacy-policy', function () {
    return view('privacy-policy');
});

Route::get('/terms-and-conditions', function () {
    return view('terms-and-condition');
});

Route::get('/refund-and-cancellation', function () {
    return view('refund');
});

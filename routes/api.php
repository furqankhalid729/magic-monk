<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Interakt\Webhook;

Route::post('/webhook', [Webhook::class, 'handle'])
    ->middleware('verify.interakt')
    ->name('interakt.webhook.handle');

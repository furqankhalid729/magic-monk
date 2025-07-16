<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Interakt\Webhook;

Route::post('/webhook', [Webhook::class, 'handle'])
    ->name('interakt.webhook.handle');

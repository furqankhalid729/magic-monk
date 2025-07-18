<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Interakt\Webhook;
use App\Http\Controllers\ApiController;

Route::post('/webhook', [Webhook::class, 'handle'])
    ->name('interakt.webhook.handle');

Route::get('/check-meta-data', [ApiController::class, 'checkMetaData'])
    ->name('api.checkMetaData');

Route::get('/get-locations', [ApiController::class, 'getNearbyLocations'])
    ->name('api.getLocations');

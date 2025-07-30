<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Interakt\Webhook;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AndroidAgentController;

Route::post('/webhook', [Webhook::class, 'handle'])
    ->name('interakt.webhook.handle');

Route::get('/check-meta-data', [ApiController::class, 'checkMetaData'])
    ->name('api.checkMetaData');

Route::get('/get-locations', [ApiController::class, 'getNearbyLocations'])
    ->name('api.getLocations');

Route::post('agent/login', [AndroidAgentController::class, 'login'])
    ->name('agent.login');

Route::get('agent/order', [AndroidAgentController::class, 'order'])
    ->name('agent.order');

Route::post('agent/token', [AndroidAgentController::class, 'storeToken'])
    ->name('agent.token');

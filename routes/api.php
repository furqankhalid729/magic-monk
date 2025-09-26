<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Interakt\Webhook;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AndroidAgentController;
use App\Http\Controllers\ReferralController;

Route::post('/webhook', [Webhook::class, 'handle'])
    ->name('interakt.webhook.handle');

Route::get('/check-meta-data', [ApiController::class, 'checkMetaData'])
    ->name('api.checkMetaData');

Route::get('/get-locations', [ApiController::class, 'getNearbyLocations'])
    ->name('api.getLocations');

Route::get('/get-stores', [ApiController::class, 'getStores'])
    ->name('api.getStores');

Route::get('/get-products', [ApiController::class, 'getProducts'])
    ->name('api.getProducts');

Route::post('agent/login', [AndroidAgentController::class, 'login'])
    ->name('agent.login');

Route::get('agent/order', [AndroidAgentController::class, 'order'])
    ->name('agent.order');

Route::post('agent/token', [AndroidAgentController::class, 'storeToken'])
    ->name('agent.token');

Route::post('agent/order/status', [AndroidAgentController::class, 'updateOrderStatus'])
    ->name('agent.order.status');
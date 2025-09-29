<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Interakt\Webhook;
use App\Http\Controllers\ApiController;
use App\Http\Controllers\AndroidAgentController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\InventoryTransferController;


Route::post('/webhook', [Webhook::class, 'handle'])
    ->name('interakt.webhook.handle');

// API Routes Group
Route::controller(ApiController::class)->group(function () {
    Route::get('/check-meta-data', 'checkMetaData')->name('api.checkMetaData');
    Route::get('/get-locations', 'getNearbyLocations')->name('api.getLocations');
    Route::get('/get-stores', 'getStores')->name('api.getStores');
    Route::get('/get-products', 'getProducts')->name('api.getProducts');
});

Route::controller(InventoryTransferController::class)->group(function () {
    Route::post('/inventory-transfer', 'store')->name('api.inventoryTransfer');
});

// Agent Routes Group
Route::prefix('agent')->controller(AndroidAgentController::class)->group(function () {
    Route::post('login', 'login')->name('agent.login');
    Route::get('order', 'order')->name('agent.order');
    Route::post('token', 'storeToken')->name('agent.token');
    Route::post('order/status', 'updateOrderStatus')->name('agent.order.status');
});
<?php

use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

Route::get('/products', [ShopController::class, 'products']);
Route::get('/products/{id}', [ShopController::class, 'product']);
Route::post('/session', [ShopController::class, 'session']);
Route::get('/orders', [ShopController::class, 'orders']);
Route::post('/orders/{id}/refund', [ShopController::class, 'refund']);
Route::get('/transfers', [ShopController::class, 'transfers']);
Route::post('/transfers', [ShopController::class, 'createTransfer']);
Route::match(['GET', 'POST'], '/payments/{route?}', [ShopController::class, 'payments'])->where('route', '.*');
Route::post('/webhooks', [ShopController::class, 'webhooks']);

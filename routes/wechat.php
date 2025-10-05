<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Wechat\MeController;
use App\Http\Controllers\Wechat\AuthController;

Route::prefix('auth')->group(function () {
    Route::get('config', [AuthController::class, 'config']);
    Route::post('login', [AuthController::class, 'login']);
    Route::get('logout', [AuthController::class, 'logout']);
});

Route::prefix('me')->group(function () {
    Route::get('products', [MeController::class, 'getProducts']);
    Route::get('product/info', [MeController::class, 'getProductInfo']);
    Route::get('integrals', [MeController::class, 'getIntegrals']);
    Route::get('total-integral', [MeController::class, 'getTotalIntegral']);
});

<?php

use App\Http\Controllers\Install\InstallController;
use Illuminate\Support\Facades\Route;

// 匹配任意路径的通配符路由
Route::any('{any}', [InstallController::class, 'redirect'])->where('any', '^(?!install).*$');

Route::prefix('install')->group(function () {
    Route::get('index', [InstallController::class, 'index'])->name('install.index');
    Route::post('start', [InstallController::class, 'start']);
    Route::get('install', [InstallController::class, 'install']);
    Route::get('environment', [InstallController::class, 'environment']);
    Route::get('config', [InstallController::class, 'getConfig']);
});

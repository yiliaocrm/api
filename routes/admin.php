<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\AdminMenuController;
use App\Http\Controllers\Admin\TenantLoginBannerController;

Route::get('/', [AuthController::class, 'home'])->withoutMiddleware(['auth:sanctum']);

Route::controller(AuthController::class)->prefix('auth')->group(function () {
    Route::get('config', 'getConfig')->withoutMiddleware(['auth:sanctum']);
    Route::post('login', 'login')->withoutMiddleware(['auth:sanctum']);
    Route::get('logout', 'logout');
    Route::get('profile', 'profile');
    Route::post('reset-password', 'resetPassword');
});

Route::controller(TenantController::class)->prefix('tenant')->group(function () {
    Route::get('login', 'login');
    Route::get('index', 'index');
    Route::post('create', 'create');
    Route::get('remove', 'remove');
    Route::get('pause', 'pause');
    Route::get('info', 'info');
    Route::get('run', 'run');
    Route::post('update', 'update');
});

Route::controller(DashboardController::class)->prefix('dashboard')->group(function () {
    Route::get('index', 'index');
});

Route::controller(ConfigController::class)->prefix('config')->group(function () {
    Route::get('load', 'load');
    Route::post('save', 'save');
});

// 租户后台菜单
Route::controller(MenuController::class)->prefix('menu')->group(function () {
    Route::get('sync', 'sync');
    Route::get('tree', 'tree');
    Route::get('info', 'info');
    Route::get('scope', 'scope');
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

// 中央后台菜单
Route::controller(AdminMenuController::class)->prefix('admin-menu')->group(function () {
    Route::get('tree', 'tree');
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

Route::controller(TenantLoginBannerController::class)->prefix('tenant-login-banner')->group(function () {
    Route::get('info', 'info');
    Route::get('index', 'index');
    Route::get('remove', 'remove');
    Route::get('toggle', 'toggle');
    Route::post('create', 'create');
    Route::post('update', 'update');
});

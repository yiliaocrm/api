<?php

declare(strict_types=1);

use App\Http\Controllers\Admin\AdminMenuController;
use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ConfigController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\Horizon\HorizonBatchesController;
use App\Http\Controllers\Admin\Horizon\HorizonDashboardController;
use App\Http\Controllers\Admin\Horizon\HorizonJobsController;
use App\Http\Controllers\Admin\Horizon\HorizonMetricsController;
use App\Http\Controllers\Admin\Horizon\HorizonMonitoringController;
use App\Http\Controllers\Admin\MenuController;
use App\Http\Controllers\Admin\TenantController;
use App\Http\Controllers\Admin\TenantLoginBannerController;
use Illuminate\Support\Facades\Route;

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
    Route::get('secret', 'secret');
    Route::post('verify', 'verify');
    Route::post('dist-sync', 'distSync');
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

// Horizon 队列监控
Route::prefix('horizon')->group(function () {
    // Dashboard
    Route::controller(HorizonDashboardController::class)->group(function () {
        Route::get('stats', 'stats');
        Route::get('workload', 'workload');
        Route::get('masters', 'masters');
    });

    // Metrics
    Route::controller(HorizonMetricsController::class)->prefix('metrics')->group(function () {
        Route::get('jobs', 'jobMetrics');
        Route::get('jobs/detail', 'jobMetricDetail');
        Route::get('queues', 'queueMetrics');
        Route::get('queues/detail', 'queueMetricDetail');
    });

    // Jobs
    Route::controller(HorizonJobsController::class)->prefix('jobs')->group(function () {
        Route::get('pending', 'pending');
        Route::get('completed', 'completed');
        Route::get('failed', 'failed');
        Route::get('failed/detail', 'failedDetail');
        Route::get('retry', 'retry');
        Route::get('silenced', 'silenced');
        Route::get('detail', 'detail');
    });

    // Monitoring
    Route::controller(HorizonMonitoringController::class)->prefix('monitoring')->group(function () {
        Route::get('index', 'index');
        Route::get('store', 'store');
        Route::get('jobs', 'jobs');
        Route::get('destroy', 'destroy');
    });

    // Batches
    Route::controller(HorizonBatchesController::class)->prefix('batches')->group(function () {
        Route::get('index', 'index');
        Route::get('detail', 'detail');
        Route::get('retry', 'retry');
    });
});

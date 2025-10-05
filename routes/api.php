<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api as Api;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ErkaiController;
use App\Http\Controllers\Api\CacheController;
use App\Http\Controllers\Api\MessageController;
use App\Http\Controllers\Api\CustomerController;
use App\Http\Controllers\Api\FollowupController;
use App\Http\Controllers\Api\TreatmentController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\CustomerPhotoController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Route::middleware('auth:api')->get('/user', function (Request $request) {
//     return $request->user();
// });


Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login'])->withoutMiddleware(['auth:sanctum']);
    Route::get('logout', [AuthController::class, 'logout']);
    Route::get('profile', [AuthController::class, 'profile']);
    Route::get('qrcode', [AuthController::class, 'qrcode']);
});

Route::prefix('cache')->group(function () {
    Route::get('items', [CacheController::class, 'items']);
    Route::get('users', [CacheController::class, 'users']);
    Route::get('rooms', [CacheController::class, 'rooms']);
    Route::get('address', [CacheController::class, 'address']);
    Route::get('mediums', [CacheController::class, 'mediums']);
    Route::get('departments', [CacheController::class, 'departments']);
    Route::get('goods-type', [CacheController::class, 'goodsTypes']);
    Route::get('product-type', [CacheController::class, 'productTypes']);
    Route::get('followup-type', [CacheController::class, 'followupTypes']);
    Route::get('followup-tool', [CacheController::class, 'followupTools']);
    Route::get('reservation-type', [CacheController::class, 'reservationTypes']);
    Route::get('product-package-type', [CacheController::class, 'productPackageTypes']);
});

Route::prefix('message')->group(function () {
    Route::get('index', [MessageController::class, 'index']);
});

Route::prefix('customer')->group(function () {
    Route::get('info', [CustomerController::class, 'info']);
    Route::get('profile', [CustomerController::class, 'profile']);
    Route::get('index', [CustomerController::class, 'index']);
    Route::get('query', [CustomerController::class, 'query']);
    Route::get('photo', [CustomerController::class, 'photo']);
    Route::get('followup', [CustomerController::class, 'followup']);
    Route::get('reservation', [CustomerController::class, 'reservation']);
    Route::post('create', [CustomerController::class, 'create']);
});

Route::prefix('reservation')->group(function () {
    Route::get('index', [ReservationController::class, 'index']);
    Route::get('info', [ReservationController::class, 'info']);
    Route::post('create', [ReservationController::class, 'create']);
});

Route::prefix('followup')->group(function () {
    Route::get('info', [FollowupController::class, 'info']);
    Route::get('index', [FollowupController::class, 'index']);
    Route::post('create', [FollowupController::class, 'create']);
    Route::post('execute', [FollowupController::class, 'execute']);
});

Route::prefix('treatment')->group(function () {
    Route::get('index', [TreatmentController::class, 'index']);
});

Route::prefix('erkai')->group(function () {
    Route::get('index', [ErkaiController::class, 'index']);
});

Route::prefix('customer-photo')->group(function () {
    Route::get('index', [CustomerPhotoController::class, 'index']);
    Route::get('info', [CustomerPhotoController::class, 'info']);
    Route::post('upload', [CustomerPhotoController::class, 'upload']);
    Route::post('create', [CustomerPhotoController::class, 'create']);
});

Route::prefix('appointment')->group(function () {
    Route::post('create', [AppointmentController::class, 'create']);
    Route::get('config', [AppointmentController::class, 'config']);
    Route::get('lists', [AppointmentController::class, 'lists']);
    Route::get('dashboard', [AppointmentController::class, 'dashboard']);
});

Route::prefix('material')->group(function () {
    Route::get('categories/index', [Api\MaterialController::class, 'indexCategory']);
    Route::post('categories/create', [Api\MaterialController::class, 'createCategory']);
    Route::post('categories/update', [Api\MaterialController::class, 'updateCategory']);
    Route::post('categories/sort', [Api\MaterialController::class, 'sortCategory']);
    Route::post('categories/disable', [Api\MaterialController::class, 'disableCategory']);
    Route::post('categories/enable', [Api\MaterialController::class, 'enableCategory']);
    Route::get('categories/remove', [Api\MaterialController::class, 'removeCategory']);

    Route::get('index', [Api\MaterialController::class, 'index']);
    Route::post('create', [Api\MaterialController::class, 'create']);
    Route::get('info', [Api\MaterialController::class, 'info']);
    Route::post('update', [Api\MaterialController::class, 'update']);
    Route::get('remove', [Api\MaterialController::class, 'remove']);

    // Route::get('download', [Api\MaterialController::class, 'download']);
    // Route::get('share', [Api\MaterialController::class, 'share']);
    // Route::get('collect', [Api\MaterialController::class, 'collect']);
    // Route::get('visit', [Api\MaterialController::class, 'visit']);
    // Route::get('preview', [Api\MaterialController::class, 'preview']);
    // Route::get('statistics', [Api\MaterialController::class, 'statistics']);
});


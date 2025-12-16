<?php

use App\Http\Controllers\Api;
use Illuminate\Support\Facades\Route;

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
    Route::post('login', [Api\AuthController::class, 'login'])->withoutMiddleware(['auth:sanctum']);
    Route::get('logout', [Api\AuthController::class, 'logout']);
    Route::get('profile', [Api\AuthController::class, 'profile']);
    Route::get('qrcode', [Api\AuthController::class, 'qrcode']);
});

Route::prefix('cache')->group(function () {
    Route::get('items', [Api\CacheController::class, 'items']);
    Route::get('users', [Api\CacheController::class, 'users']);
    Route::get('rooms', [Api\CacheController::class, 'rooms']);
    Route::get('address', [Api\CacheController::class, 'address']);
    Route::get('mediums', [Api\CacheController::class, 'mediums']);
    Route::get('departments', [Api\CacheController::class, 'departments']);
    Route::get('goods-type', [Api\CacheController::class, 'goodsTypes']);
    Route::get('product-type', [Api\CacheController::class, 'productTypes']);
    Route::get('followup-type', [Api\CacheController::class, 'followupTypes']);
    Route::get('followup-tool', [Api\CacheController::class, 'followupTools']);
    Route::get('reservation-type', [Api\CacheController::class, 'reservationTypes']);
    Route::get('product-package-type', [Api\CacheController::class, 'productPackageTypes']);
});

Route::prefix('message')->group(function () {
    Route::get('index', [Api\MessageController::class, 'index']);
});

Route::prefix('customer')->group(function () {
    Route::get('info', [Api\CustomerController::class, 'info']);
    Route::get('profile', [Api\CustomerController::class, 'profile']);
    Route::get('index', [Api\CustomerController::class, 'index']);
    Route::get('query', [Api\CustomerController::class, 'query']);
    Route::get('photo', [Api\CustomerController::class, 'photo']);
    Route::get('followup', [Api\CustomerController::class, 'followup']);
    Route::get('reservation', [Api\CustomerController::class, 'reservation']);
    Route::post('create', [Api\CustomerController::class, 'create']);
});

Route::prefix('reservation')->group(function () {
    Route::get('index', [Api\ReservationController::class, 'index']);
    Route::get('info', [Api\ReservationController::class, 'info']);
    Route::post('create', [Api\ReservationController::class, 'create']);
});

Route::prefix('followup')->group(function () {
    Route::get('info', [Api\FollowupController::class, 'info']);
    Route::get('index', [Api\FollowupController::class, 'index']);
    Route::post('create', [Api\FollowupController::class, 'create']);
    Route::post('execute', [Api\FollowupController::class, 'execute']);
});

Route::prefix('treatment')->group(function () {
    Route::get('index', [Api\TreatmentController::class, 'index']);
});

Route::prefix('erkai')->group(function () {
    Route::get('index', [Api\ErkaiController::class, 'index']);
});

Route::prefix('customer-photo')->group(function () {
    Route::get('index', [Api\CustomerPhotoController::class, 'index']);
    Route::get('info', [Api\CustomerPhotoController::class, 'info']);
    Route::post('upload', [Api\CustomerPhotoController::class, 'upload']);
    Route::post('create', [Api\CustomerPhotoController::class, 'create']);
});

Route::prefix('appointment')->group(function () {
    Route::get('index', [Api\AppointmentController::class, 'index']);
    Route::get('config', [Api\AppointmentController::class, 'config']);
    Route::get('dashboard', [Api\AppointmentController::class, 'dashboard']);
    Route::post('create', [Api\AppointmentController::class, 'create']);
});


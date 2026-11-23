<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DataController;
use App\Http\Controllers\Api\FcmController;
use App\Http\Controllers\Api\UjianController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/user', [AuthController::class, 'getProfile']);
    Route::get('/jadwals', [DataController::class, 'jadwals']);
    Route::get('/infos', [DataController::class, 'infos']);
    Route::get('/info/{id}', [DataController::class, 'detailInfo'])->whereNumber('id');
    Route::get('/ekskuls', [DataController::class, 'ekskuls']);
    Route::post('/update-fcm-token', [FcmController::class, 'update_token']);
    Route::get('/jadwal-ujian', [UjianController::class, 'getUjian']);
    Route::post('/selesai-ujian', [UjianController::class, 'selesaiUjian']);
});

<?php

use App\Http\Controllers\Api\TpsSyncController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'device.tps'])
    ->prefix('tps/{kodeTps}')
    ->group(function () {
        Route::get('snapshot', [TpsSyncController::class, 'snapshot']);
        Route::post('sync', [TpsSyncController::class, 'sync']);
    });

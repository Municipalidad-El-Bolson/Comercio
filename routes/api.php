<?php

use App\Http\Controllers\Api\UbicacionController;
use Illuminate\Support\Facades\Route;

Route::get('/ubicaciones', [UbicacionController::class, 'index']);
Route::get('/ubicaciones/{id}', [UbicacionController::class, 'show']);
Route::post('/ubicaciones', [UbicacionController::class, 'store']);
Route::put('/ubicaciones/{id}', [UbicacionController::class, 'update']);
Route::delete('/ubicaciones/{id}', [UbicacionController::class, 'destroy']);

<?php

use App\Http\Controllers\Api\TerritorioController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureApiTokenIsValid;
use App\Http\Controllers\Api\UbicacionController;

Route::prefix('v1')->middleware([EnsureApiTokenIsValid::class])->group(function () {
    // Rutas para obtener regiones, provincias y comunas de manera dinámica
    Route::get('/regiones', [UbicacionController::class, 'getRegiones']);
    Route::get('/provincias', [UbicacionController::class, 'getProvincias']); // Ahora usa query params
    Route::get('/comunas', [UbicacionController::class, 'getComunas']); // Ahora usa query params
    
    // Rutas para gestionar territorios
    Route::get('/territorios', [TerritorioController::class, 'index']);
    Route::post('/territorios', [TerritorioController::class, 'store']);
    Route::get('/territorios/{id}', [TerritorioController::class, 'show']);
    Route::put('/territorios/{id}', [TerritorioController::class, 'update']);
    Route::delete('/territorios/{id}', [TerritorioController::class, 'destroy']);
});
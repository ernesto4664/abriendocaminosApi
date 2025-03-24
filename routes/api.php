<?php

use App\Http\Controllers\Api\TerritorioController;
use Illuminate\Support\Facades\Route;
use App\Http\Middleware\EnsureApiTokenIsValid;
use App\Http\Controllers\Api\TokenController;
use App\Http\Controllers\Api\UbicacionController;
use App\Http\Controllers\Api\PlanIntervencionController;
use App\Http\Controllers\Api\EvaluacionController;
use App\Http\Controllers\Api\PreguntaController;
use App\Http\Controllers\Api\InstitucionEjecutoraController;
use App\Http\Controllers\Api\NNAController;
use App\Http\Controllers\Api\RespuestaController;
use App\Http\Controllers\Api\LineasDeIntervencionController;

use App\Http\Controllers\Api\UsuariosInstitucionController;
use App\Http\Controllers\Api\AuthUsuariosInstitucionController;

Route::prefix('v1')->middleware([EnsureApiTokenIsValid::class])->group(function () {
    // Rutas para obtener regiones, provincias y comunas de manera dinámica
    Route::get('/regiones', [UbicacionController::class, 'getRegiones']);
    Route::get('/provincias', [UbicacionController::class, 'getProvincias']); // Ahora usa query params
    Route::get('/comunas', [UbicacionController::class, 'getComunas']); // Ahora usa query params
    
    // Rutas para gestionar lineas
    Route::get('/lineas', [LineasDeIntervencionController::class, 'index']);
    Route::post('/lineas', [LineasDeIntervencionController::class, 'store']);
    Route::put('/lineas/{id}', [LineasDeIntervencionController::class, 'update']);
    Route::get('/lineas/{id}', [LineasDeIntervencionController::class, 'show']);
    Route::delete('/lineas/{id}', [LineasDeIntervencionController::class, 'destroy']);

    // Rutas para gestionar territorios
    Route::get('/territorios', [TerritorioController::class, 'index']);
    Route::post('/territorios', [TerritorioController::class, 'store']);
    Route::get('/territorios/{id}', [TerritorioController::class, 'show']);
    Route::put('/territorios/{id}', [TerritorioController::class, 'update']);
    Route::delete('/territorios/{id}', [TerritorioController::class, 'destroy']);

    // 📌 Rutas para Planes de Intervención
    Route::get('/planes', [PlanIntervencionController::class, 'index']);  // Obtener todos los planes
    Route::post('/planes', [PlanIntervencionController::class, 'store']); // Crear un nuevo plan
    Route::get('/planes/{id}', [PlanIntervencionController::class, 'show']); // Obtener un plan específico
    Route::put('/planes/{id}', [PlanIntervencionController::class, 'update']); // Actualizar un plan
    Route::delete('/planes/{id}', [PlanIntervencionController::class, 'destroy']); // Eliminar un plan
    Route::get('/planes/territorio/{id}', [PlanIntervencionController::class, 'getPlanPorTerritorio']);
    Route::get('/planes/por-linea/{linea_id}', [PlanIntervencionController::class, 'getPlanesPorLinea']);
    Route::get('/planes/{plan_id}/evaluaciones', [PlanIntervencionController::class, 'getEvaluacionesConPreguntas']);

    // 📌 Rutas para Evaluaciones
    Route::get('/evaluaciones', [EvaluacionController::class, 'index']);  
    Route::post('/evaluaciones', [EvaluacionController::class, 'store']);
    Route::get('/evaluaciones/{id}', [EvaluacionController::class, 'show']);
    Route::put('/evaluaciones/{id}', [EvaluacionController::class, 'update']);
    Route::delete('/evaluaciones/{id}', [EvaluacionController::class, 'destroy']);
    Route::get('/planes/{planId}/evaluaciones-sin-respuestas', [EvaluacionController::class, 'getEvaluacionesSinRespuestas']);

    // 📌 Rutas para Preguntas
    Route::get('/preguntas', [PreguntaController::class, 'index']);  
    Route::post('/preguntas', [PreguntaController::class, 'store']);
    Route::get('/preguntas/{id}', [PreguntaController::class, 'show']);
    Route::put('/preguntas/{id}', [PreguntaController::class, 'update']);
    Route::delete('/preguntas/{id}', [PreguntaController::class, 'destroy']);

    // 📌 Rutas para Respuestas
    Route::get('/respuestas', [RespuestaController::class, 'index']);  
    Route::post('/respuestas', [RespuestaController::class, 'store']);
    Route::get('/respuestas/{id}', [RespuestaController::class, 'show']);
    Route::put('/respuestas/{id}', [RespuestaController::class, 'update']);
    Route::delete('/respuestas/{id}', [RespuestaController::class, 'destroy']);
    
    Route::put('/respuestas-multiple', [RespuestaController::class, 'updateMultiple']);

    Route::get('/evaluaciones/{evaluacion_id}/completa', [RespuestaController::class, 'getEvaluacionCompleta']);

    // 📌 Rutas para Instituciones Ejecutoras
    Route::get('/instituciones', [InstitucionEjecutoraController::class, 'index']);  
    Route::post('/instituciones', [InstitucionEjecutoraController::class, 'store']);
    Route::get('/instituciones/{id}', [InstitucionEjecutoraController::class, 'show']);
    Route::put('/instituciones/{id}', [InstitucionEjecutoraController::class, 'update']);
    Route::delete('/instituciones/{id}', [InstitucionEjecutoraController::class, 'destroy']);

    Route::apiResource('usuarios-institucion', UsuariosInstitucionController::class);

    Route::prefix('auth-usuarios-institucion')->group(function () {
        Route::post('/register', [AuthUsuariosInstitucionController::class, 'register']); // Registrar usuario
        Route::post('/login', [AuthUsuariosInstitucionController::class, 'login']); // Login
    });

});


Route::get('/v1/get-static-token', [TokenController::class, 'getStaticToken']); // Genera el token

// Ruta protegida que requiere el token válido
Route::middleware([EnsureApiTokenIsValid::class])->get('/v1/protected-data', function() {
    return response()->json(['message' => 'Token validado con éxito']); // Ruta protegida
});




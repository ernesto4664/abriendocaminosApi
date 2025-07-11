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
use App\Http\Controllers\Api\PonderacionController;
use App\Http\Controllers\Api\DocumentosFormulariosController;

use App\Http\Controllers\Api\RegistroNnasController;
use App\Http\Controllers\Api\RegistroasplController;
use App\Http\Controllers\Api\RegistroCuidadorController;
use App\Http\Controllers\Api\EjecucionInstrumentoController;
use App\Http\Controllers\Api\GuardarRespuestasParcialesController;

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

    Route::get(
    '/planes/{plan_id}/evaluaciones',
    [\App\Http\Controllers\Api\PlanIntervencionController::class, 'getEvaluacionesConPreguntas']
    );

    Route::get('planes-completo', [PlanIntervencionController::class, 'indexCompleto']);
    
    // 📌 Rutas para Evaluaciones
    Route::get('/evaluaciones', [EvaluacionController::class, 'index']); 
    Route::get('/evaluaciones/{id}', [EvaluacionController::class, 'show']); 
    Route::post('/evaluaciones', [EvaluacionController::class, 'store']);
    
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

    Route::delete('respuestas/pregunta/{preguntaId}', [RespuestaController::class, 'destroyPorPregunta']);

    Route::post('/respuestas/pregunta/{preguntaId}/evaluacion/{evaluacionId}/limpiar', [RespuestaController::class, 'limpiarPreguntaCompleta']);

    Route::put('/respuestas-multiple', [RespuestaController::class, 'updateMultiple']);

    Route::get(
        '/evaluaciones/{evaluacion_id}/completa',
        [\App\Http\Controllers\Api\RespuestaController::class, 'getEvaluacionCompleta']
    );

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

    // 📌 Rutas para Ponderaciones

    Route::post('/ponderaciones', [PonderacionController::class, 'store']);
    Route::get('/ponderaciones/completo', [PonderacionController::class, 'completo']);
    Route::get  ('/ponderaciones/{id}/completo', [PonderacionController::class, 'completoPorId']);
    Route::put  ('/ponderaciones/{id}',          [PonderacionController::class, 'update']);

        Route::get('ponderaciones/existe-detalle/{preguntaId}', 
        [PonderacionController::class, 'existeDetallePorPregunta']
    );

    Route::delete('ponderaciones/detalle/{detalleId}', [PonderacionController::class,'destroyDetalle']);

    Route::delete('/ponderaciones/{evaluacionId}', [PonderacionController::class, 'destroy']);

    // 📌 Rutas para Documentos de Formulario NNA, cuidardor y privado de libertad
    //Route::apiResource('documentos', App\Http\Controllers\Api\DocumentosController::class)->only(['index','store','update','destroy']);
    Route::get('/documentos', [DocumentosFormulariosController::class, 'index']);
    Route::post('/documentos', [DocumentosFormulariosController::class, 'store']);
    Route::get('/documentos/{id}', [DocumentosFormulariosController::class, 'show']);
    Route::match(['post','put'], 'documentos/{id}', [DocumentosFormulariosController::class, 'update']);
    Route::delete('/documentos/{id}', [DocumentosFormulariosController::class, 'destroy']);
    Route::get('documentos/{documento}/download', [DocumentosFormulariosController::class,'download']);


    Route::get('registro-nna/documento-nna', [DocumentosFormulariosController::class, 'downloadNnaDocumento']);
    Route::get('/instituciones/buscarPorNombre', [InstitucionEjecutoraController::class, 'buscarPorNombre']);      

    Route::post('/registro-nna', [RegistroNnasController::class, 'store']);
    Route::get('/registro-nna/get-nna', [RegistroNnasController::class, 'getNna']);
    Route::post('/registro-aspl', [RegistroasplController::class, 'store']);
    Route::post('/registro-cuidador', [RegistroCuidadorController::class, 'store']);
    Route::get('/registro-cuidador/documento-cuidador', [DocumentosFormulariosController::class, 'downloadCuidadorDocumento']);

    Route::get('registro-nna/por-region/{region}', [RegistroNnasController::class, 'profesionalesPorRegion']);

    Route::get('registro-nna/profesionales/institucion/{id}', [RegistroNnasController::class, 'porInstitucion']);




   


});


 //Ejecucion de Instrumento
    Route::get('nna-con-cuidadores', [EjecucionInstrumentoController::class, 'nnaConCuidadores']);
    Route::get('nna/{id}', [EjecucionInstrumentoController::class, 'detalleNna']);
    Route::get('evaluacion/{id}', [EjecucionInstrumentoController::class, 'detalleEvaluacion']);
    Route::get('evaluaciones', [EjecucionInstrumentoController::class, 'evaluacionesActuales']);

    Route::post('/evaluaciones/respuestas-parciales', [GuardarRespuestasParcialesController::class, 'guardarRespuestasParciales']);
   Route::get('/evaluaciones/estado-nna/{nna_id}', [EjecucionInstrumentoController::class, 'estadoEvaluacionesPorNna']);
Route::get('/evaluaciones/{nnaId}/{evaluacionId}/respuestas', [EjecucionInstrumentoController::class, 'respuestasPorNnaYEvaluacion']);





Route::get('/v1/get-static-token', [TokenController::class, 'getStaticToken']); // Genera el token

// Ruta protegida que requiere el token válido
Route::middleware([EnsureApiTokenIsValid::class])->get('/v1/protected-data', function() {
    return response()->json(['message' => 'Token validado con éxito']); // Ruta protegida
});




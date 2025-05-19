<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\RespuestaOpcion;
use App\Models\RespuestaTipo;
use App\Models\RespuestaSubpregunta;
use App\Models\OpcionLikert;
use App\Models\OpcionBarraSatisfaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class EvaluacionController extends Controller
{
    /**
     * Listar todas las evaluaciones con sus preguntas
     * Devuelve directamente un array de Evaluacion
     */
    public function index()
    {
        try {
            $evaluaciones = Evaluacion::with([
                    'preguntas.respuestas.opciones',
                    'preguntas.respuestas.subpreguntas.opcionesLikert',
                    'preguntas.respuestas.opcionesBarraSatisfaccion',
                    'preguntas.tiposDeRespuesta',
                ])
                ->get()
                ->filter(function ($evaluacion) {
                    return $evaluacion->preguntas->every(function ($pregunta) {
                        $respuesta = $pregunta->respuestas->first();
                        $tipo = $pregunta->tiposDeRespuesta->first()?->tipo;

                        if (!$respuesta) {
                            return false;
                        }

                        // Si tiene opciones, barra o subpreguntas, es válida
                        if (
                            $respuesta->opciones->isNotEmpty() ||
                            $respuesta->subpreguntas->isNotEmpty() ||
                            $respuesta->opcionesBarraSatisfaccion->isNotEmpty()
                        ) {
                            return true;
                        }

                        // Si es tipo texto o número, basta con que exista la respuesta
                        if (in_array($tipo, ['texto', 'numero'])) {
                            return true;
                        }

                        // En cualquier otro caso, es incompleta
                        return false;
                    });
                })
                ->values(); // Resetear índices después del filter

            return response()->json($evaluaciones, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error al listar evaluaciones: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar evaluaciones'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear una nueva evaluación
     * Devuelve el objeto Evaluacion creado
     */
    public function store(Request $request)
    {
        $request->validate([
            'plan_id'       => 'required|exists:planes_intervencion,id',
            'nombre'        => 'required|string|max:255',
            'num_preguntas' => 'required|integer|min:1|max:50',
        ]);

        DB::beginTransaction();
        try {
            $evaluacion = Evaluacion::create($request->only('plan_id', 'nombre', 'num_preguntas'));
            DB::commit();
            return response()->json($evaluacion, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear evaluación: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear evaluación'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar una evaluación por ID
     * Devuelve directamente el objeto Evaluacion
     */
    public function show($id)
    {
        try {
            $evaluacion = Evaluacion::with('preguntas')->findOrFail($id);
            return response()->json($evaluacion, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Evaluación no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al obtener evaluación {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener evaluación'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar evaluación existente
     * Devuelve el objeto Evaluacion actualizado
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'plan_id'       => 'sometimes|required|exists:planes_intervencion,id',
            'nombre'        => 'sometimes|required|string|max:255',
            'num_preguntas' => 'sometimes|required|integer|min:1|max:50',
        ]);

        DB::beginTransaction();
        try {
            $evaluacion = Evaluacion::findOrFail($id);
            $evaluacion->update($request->only('plan_id', 'nombre', 'num_preguntas'));
            DB::commit();
            return response()->json($evaluacion, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Evaluación no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al actualizar evaluación {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar evaluación'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar evaluación
     * Devuelve un mensaje de confirmación
     */
    public function destroy($id)
    {
        try {
            $evaluacion = Evaluacion::findOrFail($id);
            $evaluacion->delete();
            return response()->json([
                'message' => 'Evaluación eliminada correctamente'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Evaluación no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al eliminar evaluación {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al eliminar evaluación'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listar evaluaciones de un plan que no tienen respuestas
     * Devuelve directamente un array de Evaluacion
     */
    public function getEvaluacionesSinRespuestas($planId)
    {
        try {
            $evaluaciones = Evaluacion::where('plan_id', $planId)
                ->with('preguntas.respuestas')
                ->get()
                ->filter(fn($e) =>
                    $e->preguntas->every(fn($p) => $p->respuestas->isEmpty())
                )
                ->values();

            return response()->json([
                'evaluaciones' => $evaluaciones
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error("Error al listar evaluaciones sin respuestas para plan {$planId}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener evaluaciones'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

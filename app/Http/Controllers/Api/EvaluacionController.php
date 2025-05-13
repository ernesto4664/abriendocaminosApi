<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class EvaluacionController extends Controller
{
    /**
     * Listar todas las evaluaciones con sus preguntas
     */
    public function index()
    {
        try {
            $evaluaciones = Evaluacion::with('preguntas')->get();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'data'    => $evaluaciones,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error al listar evaluaciones: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al listar evaluaciones',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear una nueva evaluación
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
            $evaluacion = Evaluacion::create($request->only([
                'plan_id', 'nombre', 'num_preguntas'
            ]));
            DB::commit();

            return response()->json([
                'code'    => Response::HTTP_CREATED,
                'data'    => $evaluacion,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear evaluación: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al crear evaluación',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar una evaluación por ID
     */
    public function show($id)
    {
        try {
            $evaluacion = Evaluacion::with('preguntas')->findOrFail($id);

            return response()->json([
                'code'    => Response::HTTP_OK,
                'data'    => $evaluacion,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Evaluación no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al obtener evaluación {$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener evaluación',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar evaluación existente
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
            $evaluacion->update($request->only([
                'plan_id', 'nombre', 'num_preguntas'
            ]));
            DB::commit();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'data'    => $evaluacion,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Evaluación no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al actualizar evaluación {$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al actualizar evaluación',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar evaluación
     */
    public function destroy($id)
    {
        try {
            $evaluacion = Evaluacion::findOrFail($id);
            $evaluacion->delete();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Evaluación eliminada correctamente',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Evaluación no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al eliminar evaluación {$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al eliminar evaluación',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listar evaluaciones de un plan que no tienen respuestas
     */
    public function getEvaluacionesSinRespuestas($planId)
    {
        try {
            $evaluaciones = Evaluacion::where('plan_id', $planId)
                ->with('preguntas.respuestas')
                ->get()
                ->filter(fn($e) =>
                    $e->preguntas->every(fn($p) => $p->respuestas->isEmpty())
                )->values();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'data'    => $evaluaciones,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error("Error al listar evaluaciones sin respuestas para plan {$planId}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener evaluaciones',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

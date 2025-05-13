<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PreguntaController extends Controller
{
    /**
     * Listar todas las preguntas
     */
    public function index()
    {
        try {
            $preguntas = Pregunta::all();

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $preguntas,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error al listar preguntas: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener preguntas',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear nueva pregunta
     */
    public function store(Request $request)
    {
        Log::info('📌 [STORE] Recibida solicitud para crear una Pregunta', ['data' => $request->all()]);

        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'pregunta'      => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $pregunta = Pregunta::create($request->only(['evaluacion_id', 'pregunta']));
            Log::info('📝 [STORE] Pregunta creada', ['pregunta_id' => $pregunta->id]);

            DB::commit();
            return response()->json([
                'code' => Response::HTTP_CREATED,
                'data' => $pregunta,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al crear la Pregunta: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'No se pudo crear la pregunta',
                'errors'  => ['detalle' => $e->getMessage()],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar pregunta específica
     */
    public function show($id)
    {
        try {
            $pregunta = Pregunta::findOrFail($id);

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $pregunta,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Pregunta no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('Error al obtener pregunta: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener pregunta',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar pregunta existente
     */
    public function update(Request $request, $id)
    {
        Log::info('📌 [UPDATE] Actualizando Pregunta', ['pregunta_id' => $id, 'data' => $request->all()]);

        $request->validate([
            'evaluacion_id' => 'sometimes|required|exists:evaluaciones,id',
            'pregunta'      => 'sometimes|required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->update($request->only(['evaluacion_id', 'pregunta']));
            Log::info('✅ [UPDATE] Pregunta actualizada', ['pregunta_id' => $pregunta->id]);

            DB::commit();
            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $pregunta,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Pregunta no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar la Pregunta: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'No se pudo actualizar la pregunta',
                'errors'  => ['detalle' => $e->getMessage()],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar pregunta
     */
    public function destroy($id)
    {
        Log::info('📌 [DELETE] Eliminando Pregunta', ['pregunta_id' => $id]);

        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->delete();
            Log::info('✅ [DELETE] Pregunta eliminada correctamente', ['pregunta_id' => $id]);

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Pregunta eliminada correctamente',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Pregunta no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('❌ [DELETE] Error al eliminar la Pregunta: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al eliminar la pregunta',
                'errors'  => ['detalle' => $e->getMessage()],
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

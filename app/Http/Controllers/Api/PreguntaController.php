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
    public function index()
    {
        try {
            $preguntas = Pregunta::all();
            return response()->json($preguntas, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error al listar preguntas: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener preguntas'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'pregunta'      => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $pregunta = Pregunta::create($request->only('evaluacion_id', 'pregunta'));
            DB::commit();
            return response()->json($pregunta, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al crear la Pregunta: ' . $e->getMessage());
            return response()->json([
                'message' => 'No se pudo crear la pregunta',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $pregunta = Pregunta::findOrFail($id);
            return response()->json($pregunta, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pregunta no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('Error al obtener pregunta: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener pregunta',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'evaluacion_id' => 'sometimes|required|exists:evaluaciones,id',
            'pregunta'      => 'sometimes|required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->update($request->only('evaluacion_id', 'pregunta'));
            DB::commit();
            return response()->json($pregunta, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pregunta no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar la Pregunta: ' . $e->getMessage());
            return response()->json([
                'message' => 'No se pudo actualizar la pregunta',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->delete();
            return response()->json([
                'message' => 'Pregunta eliminada correctamente'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Pregunta no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('❌ [DELETE] Error al eliminar la Pregunta: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al eliminar la pregunta',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

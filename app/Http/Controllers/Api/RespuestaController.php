<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RespuestaController extends Controller
{
    public function index()
    {
        return response()->json(Respuesta::all(), 200);
    }

    /** 📌 Guardar varias respuestas para una pregunta */
    public function store(Request $request)
    {
        $request->validate([
            'pregunta_id' => 'required|exists:preguntas,id',
            'respuestas' => 'required|array', // Debe ser un array de respuestas
            'respuestas.*.respuesta' => 'required|string|max:255',
            'respuestas.*.observaciones' => 'nullable|string',
            'respuestas.*.tipo' => ['nullable', Rule::in(['texto', 'barra_satisfaccion'])], // Ahora es opcional
        ]);

        DB::beginTransaction();
        try {
            $respuestas = [];

            foreach ($request->respuestas as $resp) {
                $respuestas[] = Respuesta::create([
                    'pregunta_id' => $request->pregunta_id,
                    'respuesta' => $resp['respuesta'],
                    'observaciones' => $resp['observaciones'] ?? null,
                    'tipo' => $resp['tipo'] ?? null, // Permite ser nulo
                ]);
            }

            DB::commit();
            return response()->json(['message' => 'Respuestas creadas con éxito', 'respuestas' => $respuestas], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al guardar respuestas', 'details' => $e->getMessage()], 500);
        }
    }

    /** 📌 Obtener respuestas de una evaluación */
    public function getRespuestasPorEvaluacion($evaluacion_id)
    {
        $evaluacion = Evaluacion::with(['preguntas.respuestas'])->find($evaluacion_id);

        if (!$evaluacion) {
            return response()->json(['error' => 'Evaluación no encontrada'], 404);
        }

        return response()->json($evaluacion);
    }

    /** 📌 Obtener una respuesta específica */
    public function show($id)
    {
        $respuesta = Respuesta::findOrFail($id);
        return response()->json($respuesta, 200);
    }

    /** 📌 Actualizar respuestas */
    public function update(Request $request, $id)
    {
        $respuesta = Respuesta::find($id);

        if (!$respuesta) {
            return response()->json(['error' => 'Respuesta no encontrada'], 404);
        }

        $request->validate([
            'respuesta' => 'sometimes|string|max:255',
            'observaciones' => 'nullable|string',
            'tipo' => ['nullable', Rule::in(['texto', 'barra_satisfaccion'])], // Ahora es opcional
        ]);

        $respuesta->update($request->all());

        return response()->json(['message' => 'Respuesta actualizada con éxito', 'respuesta' => $respuesta]);
    }

    /** 📌 Eliminar una respuesta */
    public function destroy($id)
    {
        $respuesta = Respuesta::find($id);
    
        if (!$respuesta) {
            return response()->json(['error' => 'Respuesta no encontrada'], 404);
        }
    
        $respuesta->delete();
    
        return response()->json(['message' => 'Respuesta eliminada con éxito'], 200);
    }
    
}

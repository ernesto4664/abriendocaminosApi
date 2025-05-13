<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use App\Models\MDSFApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EvaluacionController extends Controller
{
    public function index()
    {
        $respuesta = new MDSFApiResponse();

        try {
            $evaluaciones = Evaluacion::with('preguntas')->get();
            $respuesta->data = $evaluaciones;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            Log::error('Error al listar evaluaciones: ' . $e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error al listar evaluaciones';
        }

        return $respuesta->json();
    }

    public function store(Request $request)
    {
        $respuesta = new MDSFApiResponse();

        $request->validate([
            'plan_id'       => 'required|exists:planes_intervencion,id',
            'nombre'        => 'required|string|max:255',
            'num_preguntas' => 'required|integer|min:1|max:50',
        ]);

        DB::beginTransaction();
        try {
            $evaluacion = Evaluacion::create($request->only(['plan_id', 'nombre', 'num_preguntas']));
            DB::commit();

            $respuesta->data = $evaluacion;
            $respuesta->code = 201;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear evaluación: ' . $e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error al crear evaluación';
        }

        return $respuesta->json();
    }

    public function show($id)
    {
        $respuesta = new MDSFApiResponse();

        try {
            $evaluacion = Evaluacion::with('preguntas')->findOrFail($id);
            $respuesta->data = $evaluacion;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            Log::error("Error al obtener evaluación {$id}: " . $e->getMessage());
            $respuesta->code    = 404;
            $respuesta->message = 'Evaluación no encontrada';
        }

        return $respuesta->json();
    }

    public function update(Request $request, $id)
    {
        $respuesta = new MDSFApiResponse();

        $request->validate([
            'plan_id'       => 'sometimes|required|exists:planes_intervencion,id',
            'nombre'        => 'sometimes|required|string|max:255',
            'num_preguntas' => 'sometimes|required|integer|min:1|max:50',
        ]);

        DB::beginTransaction();
        try {
            $evaluacion = Evaluacion::findOrFail($id);
            $evaluacion->update($request->only(['plan_id', 'nombre', 'num_preguntas']));
            DB::commit();

            $respuesta->data = $evaluacion;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar evaluación {$id}: " . $e->getMessage());
            $respuesta->code    = isset($evaluacion) ? 500 : 404;
            $respuesta->message = isset($evaluacion)
                ? 'Error al actualizar evaluación'
                : 'Evaluación no encontrada';
        }

        return $respuesta->json();
    }

    public function destroy($id)
    {
        $respuesta = new MDSFApiResponse();

        try {
            $evaluacion = Evaluacion::findOrFail($id);
            $evaluacion->delete();

            $respuesta->message = 'Evaluación eliminada correctamente';
            $respuesta->code    = 200;
        } catch (\Exception $e) {
            Log::error("Error al eliminar evaluación {$id}: " . $e->getMessage());
            $respuesta->code    = 404;
            $respuesta->message = 'Evaluación no encontrada o no pudo eliminarse';
        }

        return $respuesta->json();
    }

    public function getEvaluacionesSinRespuestas($planId)
    {
        $respuesta = new MDSFApiResponse();

        try {
            $evaluaciones = Evaluacion::where('plan_id', $planId)
                ->with('preguntas.respuestas')
                ->get()
                ->filter(fn($e) => $e->preguntas->every(fn($p) => $p->respuestas->isEmpty()))
                ->values();

            $respuesta->data = $evaluaciones;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            Log::error("Error al listar evaluaciones sin respuestas para plan {$planId}: " . $e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error al obtener evaluaciones';
        }

        return $respuesta->json();
    }
}

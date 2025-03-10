<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EvaluacionController extends Controller {
    
    public function index() {

        return response()->json(Evaluacion::with('preguntas')->get(), 200);
    }

    public function store(Request $request) {

        Log::info('📌 [STORE] Recibida solicitud para crear una Evaluación', ['data' => $request->all()]);

        $request->validate([
            'plan_id' => 'required|exists:planes_intervencion,id', // ✅ Tabla corregida
            'nombre' => 'required|string|max:255',
            'num_preguntas' => 'required|integer|min:1|max:50'
        ]);

        DB::beginTransaction();

        try {
            $evaluacion = Evaluacion::create($request->all());

            Log::info('📝 [STORE] Evaluación creada', ['evaluacion_id' => $evaluacion->id]);

            DB::commit();
            return response()->json($evaluacion, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al crear la Evaluación', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo crear la evaluación', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function show($id) {

        return response()->json(Evaluacion::with('preguntas')->findOrFail($id), 200);
    }

    public function update(Request $request, $id) {

        Log::info('📌 [UPDATE] Actualizando Evaluación', ['evaluacion_id' => $id, 'data' => $request->all()]);

        $evaluacion = Evaluacion::findOrFail($id);
        DB::beginTransaction();

        try {
            $evaluacion->update($request->all());

            Log::info('✅ [UPDATE] Evaluación actualizada', ['evaluacion_id' => $evaluacion->id]);

            DB::commit();
            return response()->json($evaluacion, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar la Evaluación', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo actualizar la evaluación', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        
        Evaluacion::destroy($id);
        return response()->json(['message' => 'Evaluación eliminada correctamente'], 200);
    }

    public function getEvaluacionesSinRespuestas($planId)
    {
        $evaluaciones = Evaluacion::where('plan_id', $planId)
            ->with(['preguntas'])
            ->get()
            ->filter(function ($evaluacion) {
                return !$evaluacion->preguntas->some(fn($p) => $p->respuestas()->exists());
            })
            ->values(); // ✅ Reiniciar índices del array
    
        return response()->json(['evaluaciones' => $evaluaciones]);
    }
    

}

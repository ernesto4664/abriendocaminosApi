<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanIntervencion;
use App\Models\Evaluacion;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PlanIntervencionController extends Controller {

    public function index() {
        return response()->json(PlanIntervencion::with('evaluaciones')->get(), 200);
    }

    public function store(Request $request) {
        Log::info('📌 [STORE] Recibida solicitud para crear un Plan de Intervención', [
            'data' => $request->all()
        ]);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'linea' => 'required|in:1,2',
            'evaluaciones' => 'required|array',
            'evaluaciones.*.nombre' => 'required|string|max:255',
            'evaluaciones.*.preguntas' => 'required|array',
            'evaluaciones.*.preguntas.*.pregunta' => 'required|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            Log::info('✅ [STORE] Validación exitosa');

            // Guardar el Plan de Intervención
            $plan = PlanIntervencion::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'linea' => $request->linea
            ]);

            Log::info('📝 [STORE] Plan de intervención creado', [
                'plan_id' => $plan->id
            ]);

            // Guardar Evaluaciones
            foreach ($request->evaluaciones as $evaluacionData) {
                $evaluacion = Evaluacion::create([
                    'plan_id' => $plan->id,
                    'nombre' => $evaluacionData['nombre']
                ]);

                Log::info('📝 [STORE] Evaluación creada', [
                    'evaluacion_id' => $evaluacion->id,
                    'plan_id' => $plan->id
                ]);

                // Guardar Preguntas de cada Evaluación
                foreach ($evaluacionData['preguntas'] as $preguntaData) {
                    $pregunta = Pregunta::create([
                        'evaluacion_id' => $evaluacion->id,
                        'pregunta' => $preguntaData['pregunta']
                    ]);

                    Log::info('📝 [STORE] Pregunta creada', [
                        'pregunta_id' => $pregunta->id,
                        'evaluacion_id' => $evaluacion->id
                    ]);
                }
            }

            DB::commit();
            Log::info('✅ [STORE] Transacción completada con éxito');

            return response()->json($plan->load('evaluaciones.preguntas'), 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al crear el Plan de Intervención', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json(['error' => 'No se pudo crear el plan de intervención', 'detalle' => $e->getMessage()], 500);
        }
    }
    

    public function show($id) {
        $plan = PlanIntervencion::with(['evaluaciones.preguntas'])->findOrFail($id);
        return response()->json($plan, 200);
    }
    
    public function update(Request $request, $id) {
        Log::info('📌 [UPDATE] Iniciando actualización del Plan de Intervención', ['plan_id' => $id, 'data' => $request->all()]);
  
        $plan = PlanIntervencion::findOrFail($id);
        DB::beginTransaction();
  
        try {
            // ✅ 1. Actualizar el Plan de Intervención
            $plan->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'linea' => $request->linea
            ]);
            Log::info('✅ [UPDATE] Plan actualizado correctamente', ['plan_id' => $plan->id]);
  
            // ✅ 2. Manejar Evaluaciones
            if ($request->has('evaluaciones')) {
                foreach ($request->evaluaciones as $evaluacionData) {
                    if (!empty($evaluacionData['eliminar']) && $evaluacionData['eliminar'] === true) {
                        Pregunta::where('evaluacion_id', $evaluacionData['id'])->delete();
                        Evaluacion::findOrFail($evaluacionData['id'])->delete();
                        Log::info('🗑️ [DELETE] Evaluación eliminada', ['evaluacion_id' => $evaluacionData['id']]);
                        continue; // 🔥 IMPORTANTE: Saltamos a la siguiente iteración
                    }
                    
  
                    if (isset($evaluacionData['id'])) {
                        $evaluacion = Evaluacion::findOrFail($evaluacionData['id']);
                        $evaluacion->update(['nombre' => $evaluacionData['nombre']]);
                        Log::info('✅ [UPDATE] Evaluación actualizada', ['evaluacion_id' => $evaluacion->id]);
                    } else {
                        $evaluacion = Evaluacion::create([
                            'plan_id' => $plan->id,
                            'nombre' => $evaluacionData['nombre']
                        ]);
                        Log::info('🆕 [CREATE] Nueva Evaluación creada', ['evaluacion_id' => $evaluacion->id]);
                    }
  
                    // ✅ 3. Manejar Preguntas dentro de cada Evaluación
                    if (isset($evaluacionData['preguntas'])) {
                        foreach ($evaluacionData['preguntas'] as $preguntaData) {
                            if (!empty($preguntaData['eliminar'])) {
                                // ❌ Eliminar Pregunta
                                Pregunta::findOrFail($preguntaData['id'])->delete();
                                Log::info('🗑️ [DELETE] Pregunta eliminada', ['pregunta_id' => $preguntaData['id']]);
                                continue;
                            }
  
                            if (isset($preguntaData['id'])) {
                                $pregunta = Pregunta::findOrFail($preguntaData['id']);
                                $pregunta->update(['pregunta' => $preguntaData['pregunta']]);
                                Log::info('✅ [UPDATE] Pregunta actualizada', ['pregunta_id' => $pregunta->id]);
                            } else {
                                Pregunta::create([
                                    'evaluacion_id' => $evaluacion->id,
                                    'pregunta' => $preguntaData['pregunta']
                                ]);
                                Log::info('🆕 [CREATE] Nueva Pregunta creada');
                            }
                        }
                    }
                }
            }
  
            DB::commit();
            return response()->json($plan->load('evaluaciones.preguntas'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar el Plan de Intervención', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo actualizar el plan', 'detalle' => $e->getMessage()], 500);
        }
    }
  
  
     public function destroy($id) {
        PlanIntervencion::destroy($id);
        return response()->json(['message' => 'Plan eliminado correctamente'], 200);
    }
}


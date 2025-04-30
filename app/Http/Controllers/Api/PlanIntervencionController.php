<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanIntervencion;
use App\Models\Evaluacion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\RespuestaOpcion;
use App\Models\RespuestaSubpregunta;
use App\Models\RespuestaTipo;
use App\Models\LineasDeIntervencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


class PlanIntervencionController extends Controller {

    public function index() {

        $planes = PlanIntervencion::with(['evaluaciones', 'linea'])->get();
    
        return response()->json($planes, 200);
    }

    public function indexCompleto()
    {
        Log::info('[indexCompleto] Inicio del método');

        // 1) Cargo los planes con todo el árbol
        $planes = PlanIntervencion::with([
            'evaluaciones' => function($qe) {
                $qe->with([
                    'preguntas' => function($qp) {
                        $qp->with([
                            'tiposDeRespuesta',
                            'respuestas.opciones',
                            'respuestas.subpreguntas.opcionesLikert',
                            'respuestas.opcionesBarraSatisfaccion',
                            'respuestas.opcionesLikert',
                        ]);
                    },
                ]);
            },
        ])->get();

        Log::info('[indexCompleto] Planes cargados', ['planes_count' => $planes->count()]);

        foreach ($planes as $plan) {
            Log::info("[indexCompleto] Plan ID={$plan->id}", [
                'evaluaciones_count' => $plan->evaluaciones->count()
            ]);

            foreach ($plan->evaluaciones as $eval) {
                Log::info("  [indexCompleto] Evaluación ID={$eval->id}", [
                    'preguntas_count' => $eval->preguntas->count()
                ]);

                foreach ($eval->preguntas as $preg) {
                    Log::info("    [indexCompleto] Pregunta ID={$preg->id}", [
                        'tipos_count'      => $preg->tiposDeRespuesta->count(),
                        'respuestas_count' => $preg->respuestas->count()
                    ]);
                }
            }
        }

        Log::info('[indexCompleto] Terminó de registrar detalles, devolviendo JSON');
        return response()->json($planes, 200);
    }
       
    public function store(Request $request) { 

        Log::info('📌 [STORE] Recibida solicitud para crear un Plan de Intervención', [
            'data' => $request->all()
        ]);
    
        $request->validate([
            'nombre' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'linea_id' => 'required|exists:lineasdeintervenciones,id',
            'evaluaciones' => 'required|array',
            'evaluaciones.*.nombre' => 'required|string|max:255',
            'evaluaciones.*.preguntas' => 'required|array',
            'evaluaciones.*.preguntas.*.pregunta' => 'required|string|max:255',
        ]);
    
        DB::beginTransaction();
    
        try {
            Log::info('✅ [STORE] Validación exitosa');
    
            // Guardar el Plan de Intervención con la nueva columna `linea_id`
            $plan = PlanIntervencion::create([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'linea_id' => $request->linea_id
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
    
            return response()->json($plan->load('evaluaciones.preguntas', 'linea'), 201);
    
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

        $plan = PlanIntervencion::with(['evaluaciones.preguntas', 'linea'])->findOrFail($id);
    
        return response()->json($plan, 200);
    }
    
    public function update(Request $request, $id) {

        Log::info('📌 [UPDATE] Iniciando actualización del Plan de Intervención', ['plan_id' => $id, 'data' => $request->all()]);

        $plan = PlanIntervencion::findOrFail($id);
        DB::beginTransaction();

        try {
            // ✅ 1. Actualizar el Plan de Intervención con `linea_id`
            $plan->update([
                'nombre' => $request->nombre,
                'descripcion' => $request->descripcion,
                'linea_id' => $request->linea_id
            ]);
            Log::info('✅ [UPDATE] Plan actualizado correctamente', ['plan_id' => $plan->id]);

            // ✅ 2. Manejar Evaluaciones
            if ($request->has('evaluaciones')) {
                foreach ($request->evaluaciones as $evaluacionData) {
                    if (!empty($evaluacionData['eliminar']) && $evaluacionData['eliminar'] === true) {
                        Pregunta::where('evaluacion_id', $evaluacionData['id'])->delete();
                        Evaluacion::findOrFail($evaluacionData['id'])->delete();
                        Log::info('🗑️ [DELETE] Evaluación eliminada', ['evaluacion_id' => $evaluacionData['id']]);
                        continue; 
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
            return response()->json($plan->load('evaluaciones.preguntas', 'linea'), 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar el Plan de Intervención', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo actualizar el plan', 'detalle' => $e->getMessage()], 500);
        }
    }
 
     public function destroy($id) 
     {
        PlanIntervencion::destroy($id);
        return response()->json(['message' => 'Plan eliminado correctamente'], 200);
    }

    public function getPlanPorTerritorio($territorioId)
    {
        // Buscar el territorio y asegurarnos de que tiene `linea_id`
        $territorio = DB::table('territorios')
            ->where('id', $territorioId)
            ->first();
    
        if (!$territorio) {
            return response()->json(['error' => 'Territorio no encontrado'], 404);
        }
    
        \Log::info("Territorio encontrado:", (array) $territorio);
    
        if (!isset($territorio->linea_id)) {
            return response()->json(['error' => 'No se encontró la línea para este territorio'], 404);
        }
    
        \Log::info("Línea ID encontrada:", ['linea_id' => $territorio->linea_id]);
    
        // Buscar el plan de intervención asociado a esa línea
        $plan = PlanIntervencion::where('linea_id', $territorio->linea_id)->first();
    
        if (!$plan) {
            return response()->json(['error' => 'No hay plan de intervención para esta línea'], 404);
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Plan de intervención encontrado',
            'data' => $plan
        ]);
    }

    public function getPlanesPorLinea($linea_id)
{
    // Buscar planes que coincidan con la línea de intervención
    $planes = PlanIntervencion::where('linea_id', $linea_id)->get();

    if ($planes->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No hay planes de intervención para esta línea'], 404);
    }

    return response()->json(['success' => true, 'planes' => $planes]);
}

public function getEvaluacionesConPreguntas($plan_id)
{
    $plan = PlanIntervencion::with(['evaluaciones.preguntas'])->find($plan_id);

    if (!$plan) {
        return response()->json(['error' => 'Plan de intervención no encontrado'], 404);
    }

    return response()->json($plan);
}


    
     
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use App\Models\PlanIntervencion;
use App\Models\Pregunta;
use App\Models\RespuestaTipo;
use App\Models\RespuestaOpcion;
use App\Models\RespuestaSubpregunta;
use App\Models\OpcionLikert;
use App\Models\OpcionBarraSatisfaccion;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RespuestaController extends Controller
{
    /** 📌 Obtener todas las evaluaciones con preguntas y respuestas */
    public function index()
    {
        $evaluaciones = Evaluacion::with([
            'preguntas' => function ($query) {
                $query->with([
                    'respuestas' => function ($query) {
                        $query->with([
                            'opciones', 
                            'subpreguntas.opciones', 
                            'opcionesBarraSatisfaccion', 
                            'opcionesLikert'
                        ]);
                    },
                    'tiposDeRespuesta' // ✅ Incluir los tipos de respuesta para cada pregunta
                ]);
            }
        ])->get();
    
        return response()->json($evaluaciones, 200);
    }

    /** 📌 Guardar varias respuestas para una evaluación */
    public function store(Request $request)
    {
        Log::info("📡 Recibiendo respuestas:", $request->all());
    
        // ✅ Validación
        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'respuestas' => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.tipo' => ['required', Rule::in([
                'texto', 'barra_satisfaccion', '5emojis', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero', 'opcion', 'opcion_personalizada'
            ])],
            'respuestas.*.observaciones' => 'nullable|string',
            'respuestas.*.respuesta' => 'nullable|string',
            'respuestas.*.opciones' => 'nullable|array',
            'respuestas.*.subpreguntas' => 'nullable|array',
        ]);
    
        DB::beginTransaction();
        try {
            $respuestas = [];
    
            foreach ($request->respuestas as $resp) {
                // ✅ Determinar si la respuesta debe ser un input vacío (texto o número)
                $respuestaValor = in_array($resp['tipo'], ['texto', 'numero']) ? '' : ($resp['respuesta'] ?? null);
    
                // ✅ Crear la respuesta SIN almacenar `tipo`
                $nuevaRespuesta = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id' => $resp['pregunta_id'],
                    'respuesta' => $respuestaValor,
                    'observaciones' => $resp['observaciones'] ?? null
                ]);
    
                // ✅ Guardar tipo en `respuesta_tipos`
                RespuestaTipo::create([
                    'pregunta_id' => $resp['pregunta_id'],
                    'tipo' => $resp['tipo']
                ]);
    
                // ✅ Guardar opciones en `respuestas_opciones` solo si el tipo lo requiere
                if (!empty($resp['opciones']) && in_array($resp['tipo'], [
                    'opcion_personalizada', 'si_no', 'si_no_noestoyseguro', '5emojis'
                ])) {
                    $this->guardarOpciones($nuevaRespuesta, $resp['opciones']);
                }
    
                // ✅ Guardar opciones de barra de satisfacción en su tabla específica
                if ($resp['tipo'] === 'barra_satisfaccion') {
                    $this->guardarBarraSatisfaccion($nuevaRespuesta);
                }
    
                // ✅ Guardar subpreguntas de tipo Likert si existen
                if ($resp['tipo'] === 'likert' && !empty($resp['subpreguntas'])) {
                    $this->guardarSubpreguntasLikert($nuevaRespuesta, $resp['subpreguntas']);
                }
    
                $respuestas[] = $nuevaRespuesta;
            }
    
            DB::commit();
            return response()->json(['message' => 'Respuestas guardadas con éxito', 'respuestas' => $respuestas], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Error al guardar respuestas: " . $e->getMessage());
            return response()->json(['error' => 'Error al guardar respuestas', 'details' => $e->getMessage()], 500);
        }
    }
    
   
    /** 📌 Guardar opciones personalizadas */
    private function guardarOpcionesPersonalizadas(Respuesta $respuesta, array $opciones)
    {
        foreach ($opciones as $opcion) {
            RespuestaOpcion::create([
                'respuesta_id' => $respuesta->id,
                'label' => $opcion['label'] ?? 'Opción sin título',
                'valor' => $opcion['valor'] ?? null,
            ]);
        }
    }

    /** 📌 Actualizar múltiples respuestas en una evaluación */
    public function updateMultiple(Request $request)
    {
        Log::info("📥 Actualizando respuestas:", $request->all());
    
        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'respuestas' => 'required|array|min:1',
            'respuestas.*.id' => 'nullable|exists:respuestas,id',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.tipo' => ['required', Rule::in([
                'texto', 'barra_satisfaccion', '5emojis', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero', 'opcion', 'opcion_personalizada'
            ])],
            'respuestas.*.observaciones' => 'nullable|string',
            'respuestas.*.respuesta' => 'nullable|string',
            'respuestas.*.opciones' => 'nullable|array',
            'respuestas.*.subpreguntas' => 'nullable|array',
        ]);
    
        DB::beginTransaction();
        try {
            foreach ($request->respuestas as $data) {
                // ✅ Eliminar respuestas previas relacionadas con la misma pregunta y evaluación
                $respuestasAnteriores = Respuesta::where('evaluacion_id', $request->evaluacion_id)
                    ->where('pregunta_id', $data['pregunta_id'])
                    ->get();
    
                foreach ($respuestasAnteriores as $old) {
                    // Eliminar respuestas_opciones
                    RespuestaOpcion::where('respuesta_id', $old->id)->delete();
                    // Eliminar opciones barra satisfacción
                    OpcionBarraSatisfaccion::where('respuesta_id', $old->id)->delete();
                    // Eliminar subpreguntas likert y sus opciones
                    $subpreguntas = RespuestaSubpregunta::where('respuesta_id', $old->id)->get();
                    foreach ($subpreguntas as $sub) {
                        OpcionLikert::where('subpregunta_id', $sub->id)->delete();
                        $sub->delete();
                    }
                    // Eliminar la respuesta
                    $old->delete();
                }
    
                // ✅ Eliminar tipo de respuesta anterior
                RespuestaTipo::where('pregunta_id', $data['pregunta_id'])->delete();
    
                // ✅ Crear nueva respuesta
                $respuesta = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id' => $data['pregunta_id'],
                    'respuesta' => $data['respuesta'] ?? null,
                    'observaciones' => $data['observaciones'] ?? null
                ]);
    
                // ✅ Asignar nuevo tipo
                RespuestaTipo::create([
                    'pregunta_id' => $data['pregunta_id'],
                    'tipo' => $data['tipo']
                ]);
    
                // ✅ Guardar opciones (si existen)
                if (!empty($data['opciones'])) {
                    $this->actualizarOpciones($respuesta, $data['opciones']);
                }
    
                // ✅ Guardar subpreguntas Likert
                if (!empty($data['subpreguntas']) && $data['tipo'] === 'likert') {
                    $this->guardarSubpreguntasLikert($respuesta, $data['subpreguntas']);
                }
    
                // ✅ Guardar barra satisfacción
                if ($data['tipo'] === 'barra_satisfaccion') {
                    $this->guardarBarraSatisfaccion($respuesta);
                }
            }
    
            DB::commit();
            return response()->json(['message' => '✅ Respuestas actualizadas correctamente'], 200);
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("❌ Error en updateMultiple: " . $e->getMessage());
            return response()->json(['error' => 'Error al actualizar respuestas', 'details' => $e->getMessage()], 500);
        }
    }
    

    private function actualizarOpciones($respuesta, $nuevasOpciones)
    {
        $opcionesActuales = $respuesta->opciones()->get();
    
        // 🔍 Comparar si las nuevas opciones son distintas a las actuales
        $diferentes = count($opcionesActuales) !== count($nuevasOpciones);
    
        if (!$diferentes) {
            foreach ($opcionesActuales as $index => $opcionActual) {
                if (
                    $opcionActual->label !== ($nuevasOpciones[$index]['label'] ?? null) ||
                    $opcionActual->value !== ($nuevasOpciones[$index]['value'] ?? null)
                ) {
                    $diferentes = true;
                    break;
                }
            }
        }
    
        if ($diferentes) {
            // 🔄 Elimina las existentes solo si hubo cambios
            $respuesta->opciones()->delete();
    
            // 🆕 Inserta nuevas
            foreach ($nuevasOpciones as $opcion) {
                $respuesta->opciones()->create([
                    'label' => $opcion['label'] ?? null,
                    'value' => $opcion['value'] ?? null,
                ]);
            }
        }
    }
    

    private function actualizarBarraSatisfaccion(Respuesta $respuesta)
    {
        $valoresActuales = OpcionBarraSatisfaccion::where('respuesta_id', $respuesta->id)
            ->orderBy('valor')
            ->pluck('valor')
            ->toArray();
    
        $esperados = range(0, 10);
    
        // 🧐 Si ya están correctos, no hacer nada
        if ($valoresActuales === $esperados) return;
    
        // 🔄 Si no, eliminamos y reinsertamos
        OpcionBarraSatisfaccion::where('respuesta_id', $respuesta->id)->delete();
    
        foreach ($esperados as $valor) {
            OpcionBarraSatisfaccion::create([
                'respuesta_id' => $respuesta->id,
                'valor' => $valor
            ]);
        }
    }


    private function actualizarSubpreguntasLikert(Respuesta $respuesta, array $nuevasSubpreguntas)
    {
        $subpreguntasActuales = RespuestaSubpregunta::where('respuesta_id', $respuesta->id)->with('opciones')->get();
    
        // 🔍 Comparamos estructura
        $diferentes = count($subpreguntasActuales) !== count($nuevasSubpreguntas);
    
        if (!$diferentes) {
            foreach ($subpreguntasActuales as $index => $subpreguntaActual) {
                if ($subpreguntaActual->texto !== $nuevasSubpreguntas[$index]['texto']) {
                    $diferentes = true;
                    break;
                }
    
                $opcionesActuales = $subpreguntaActual->opciones;
                $opcionesNuevas = $nuevasSubpreguntas[$index]['opciones'];
    
                if (count($opcionesActuales) !== count($opcionesNuevas)) {
                    $diferentes = true;
                    break;
                }
    
                foreach ($opcionesActuales as $j => $opcion) {
                    if ($opcion->label !== $opcionesNuevas[$j]['label']) {
                        $diferentes = true;
                        break 2;
                    }
                }
            }
        }
    
        if ($diferentes) {
            // 🧹 Eliminamos todo lo anterior si hubo cambios
            $idsSubpreguntas = $subpreguntasActuales->pluck('id');
            OpcionLikert::whereIn('subpregunta_id', $idsSubpreguntas)->delete();
            RespuestaSubpregunta::whereIn('id', $idsSubpreguntas)->delete();
    
            // 🆕 Insertamos los nuevos valores
            foreach ($nuevasSubpreguntas as $subpregunta) {
                $nueva = RespuestaSubpregunta::create([
                    'respuesta_id' => $respuesta->id,
                    'texto' => $subpregunta['texto']
                ]);
    
                foreach ($subpregunta['opciones'] as $opcion) {
                    OpcionLikert::create([
                        'subpregunta_id' => $nueva->id,
                        'label' => $opcion['label'],
                        'respuesta_id' => $respuesta->id,
                    ]);
                }
            }
        }
    }



    /** 📌 Guardar opciones */
    private function guardarOpciones(Respuesta $respuesta, array $opciones)
    {
        foreach ($opciones as $opcion) {
            RespuestaOpcion::create([
                'respuesta_id' => $respuesta->id,
                'label' => $opcion['label'] ?? 'Opción sin título',
                'valor' => $opcion['valor'] ?? null,
            ]);
        }
    }

    /** 📌 Guardar barra de satisfacción */
    private function guardarBarraSatisfaccion(Respuesta $respuesta)
    {
        for ($i = 0; $i <= 10; $i++) {
            OpcionBarraSatisfaccion::create([
                'respuesta_id' => $respuesta->id,
                'valor' => $i
            ]);
        }
    }

    /** 📌 Guardar subpreguntas de tipo Likert */
    private function guardarSubpreguntasLikert(Respuesta $respuesta, array $subpreguntas)
    {
        foreach ($subpreguntas as $subpregunta) {
            $nuevaSubpregunta = RespuestaSubpregunta::create([
                'respuesta_id' => $respuesta->id, 
                'texto' => $subpregunta['texto'],
            ]);
    
            foreach ($subpregunta['opciones'] as $opcion) {
                OpcionLikert::create([
                    'subpregunta_id' => $nuevaSubpregunta->id,
                    'label' => $opcion['label'],
                    'respuesta_id' => $respuesta->id,
                ]);
            }
        }
    }

    /** 📌 Obtener evaluación completa con preguntas y respuestas */
    public function getEvaluacionCompleta($evaluacion_id)
    {
        Log::info("📥 Solicitando evaluación completa para ID: {$evaluacion_id}");
    
        $evaluacion = Evaluacion::with([
            'preguntas' => function ($query) {
                $query->with([
                    'respuestas' => function ($query) {
                        $query->with([
                            'opciones', 
                            'subpreguntas.opciones', 
                            'opcionesBarraSatisfaccion', 
                            'opcionesLikert'
                        ]);
                    },
                    'tiposDeRespuesta' // 🚀 Asegurar que cargue los tipos de respuesta
                ]);
            }
        ])->find($evaluacion_id);
    
        if (!$evaluacion) {
            return response()->json(['error' => 'Evaluación no encontrada'], 404);
        }
    
        return response()->json($evaluacion, 200);
    }
}

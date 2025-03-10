<?php

namespace App\Http\Controllers\Api;


use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use App\Models\RespuestaOpcion;
use App\Models\RespuestaSubpregunta;
use App\Models\OpcionLikert;
use App\Models\OpcionBarraSatisfaccion;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class RespuestaController extends Controller
{
    public function index()
    {
        $respuestas = Respuesta::with([
            'opciones', 
            'subpreguntas', 
            'pregunta.evaluacion' // 🔹 Agregamos la relación a evaluación
        ])->get();
    
        return response()->json($respuestas, 200);
    }

    /** 📌 Guardar varias respuestas para una pregunta */
    public function store(Request $request)
    {
        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'respuestas' => 'required|array',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id', // ✅ Verifica que el ID existe en la tabla
            'respuestas.*.tipo' => ['required', Rule::in([
                'texto', 'barra_satisfaccion', '5emojis', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero', 'opcion'
            ])],
            'respuestas.*.observaciones' => 'nullable|string',
            'respuestas.*.respuesta' => 'nullable|string',
        ]);
            
        DB::beginTransaction();
        try {
            $respuestas = [];
    
            foreach ($request->respuestas as $resp) {
                $nuevaRespuesta = Respuesta::create([
                    'pregunta_id' => $resp['pregunta_id'],
                    'respuesta' => isset($resp['respuesta']) ? $resp['respuesta'] : null,
                    'observaciones' => $resp['observaciones'] ?? null,
                    'tipo' => $resp['tipo']
                ]);
    
                // ✅ Guardar Opciones de Sí/No y otras simples
                if (!empty($resp['opciones']) && in_array($resp['tipo'], ['si_no', 'si_no_noestoyseguro', '5emojis'])) {
                    foreach ($resp['opciones'] as $opcion) {
                        RespuestaOpcion::create([
                            'respuesta_id' => $nuevaRespuesta->id,
                            'label' => $opcion['label'],
                            'valor' => $opcion['valor'] ?? null,
                        ]);
                    }
                }
    
                // ✅ Guardar Opciones de Barra de Satisfacción (0-10)
                if ($resp['tipo'] === 'barra_satisfaccion') {
                    for ($i = 0; $i <= 10; $i++) {
                        OpcionBarraSatisfaccion::create([
                            'respuesta_id' => $nuevaRespuesta->id,
                            'valor' => $i
                        ]);
                    }
                }
    
                // ✅ Guardar Subpreguntas del Likert con sus opciones
                if (!empty($resp['subpreguntas']) && $resp['tipo'] === 'likert') {
                    foreach ($resp['subpreguntas'] as $subpregunta) {
                        $nuevaSubpregunta = RespuestaSubpregunta::create([
                            'respuesta_id' => $nuevaRespuesta->id,
                            'texto' => $subpregunta['texto'],
                        ]);
    
                        if (!empty($subpregunta['opciones'])) {
                            foreach ($subpregunta['opciones'] as $opcion) {
                                OpcionLikert::create([
                                    'subpregunta_id' => $nuevaSubpregunta->id,
                                    'label' => $opcion['label'],
                                ]);
                            }
                        }
                    }
                }
    
                // ✅ Incluir las relaciones en la respuesta
                $nuevaRespuesta->load('opciones', 'subpreguntas.opciones'); 
                $respuestas[] = $nuevaRespuesta;
            }
    
            DB::commit();
            return response()->json([
                'message' => 'Respuestas creadas con éxito', 
                'respuestas' => $respuestas
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Error al guardar respuestas', 
                'details' => $e->getMessage()
            ], 500);
        }
    }
    
    /** 📌 Obtener respuestas de una evaluación */
    public function getRespuestasPorEvaluacion($evaluacion_id)
    {
        $evaluacion = Evaluacion::with(['preguntas.respuestas.opciones', 'preguntas.respuestas.subpreguntas'])->find($evaluacion_id);
    
        if (!$evaluacion) {
            return response()->json(['error' => 'Evaluación no encontrada'], 404);
        }
    
        return response()->json($evaluacion);
    }
    

    /** 📌 Obtener una respuesta específica */
    public function show($id)
    {
        $respuesta = Respuesta::with(['opciones', 'subpreguntas'])->find($id);
    
        if (!$respuesta) {
            return response()->json(['error' => 'Respuesta no encontrada'], 404);
        }
    
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
            'tipo' => ['nullable', Rule::in(['texto', 'barra_satisfaccion', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero'])],
            'opciones' => 'nullable|array',
            'opciones.*.label' => 'required_with:opciones|string|max:255',
            'opciones.*.valor' => 'nullable|string|max:255',
            'subpreguntas' => 'nullable|array',
            'subpreguntas.*.texto' => 'required_with:subpreguntas|string|max:255'
        ]);
    
        DB::beginTransaction();
        try {
            // Actualizar la respuesta principal
            $respuesta->update([
                'respuesta' => $request->input('respuesta', $respuesta->respuesta),
                'observaciones' => $request->input('observaciones', $respuesta->observaciones),
                'tipo' => $request->input('tipo', $respuesta->tipo)
            ]);
    
            // Si hay opciones, eliminarlas y agregar las nuevas
            if ($request->has('opciones')) {
                $respuesta->opciones()->delete();
                foreach ($request->opciones as $opcion) {
                    RespuestaOpcion::create([
                        'respuesta_id' => $respuesta->id,
                        'label' => $opcion['label'],
                        'valor' => $opcion['valor'] ?? null,
                    ]);
                }
            }
    
            // Si hay subpreguntas, eliminarlas y agregar las nuevas
            if ($request->has('subpreguntas')) {
                $respuesta->subpreguntas()->delete();
                foreach ($request->subpreguntas as $subpregunta) {
                    RespuestaSubpregunta::create([
                        'respuesta_id' => $respuesta->id,
                        'texto' => $subpregunta['texto']
                    ]);
                }
            }
    
            DB::commit();
            return response()->json(['message' => 'Respuesta actualizada con éxito', 'respuesta' => $respuesta->load(['opciones', 'subpreguntas'])], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al actualizar respuesta', 'details' => $e->getMessage()], 500);
        }
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

    public function getEvaluacionCompleta($evaluacion_id)
{
    $evaluacion = Evaluacion::with([
        'preguntas.respuestas' => function ($query) {
            $query->with(['opciones', 'subpreguntas.opciones']);
        }
    ])->find($evaluacion_id);

    if (!$evaluacion) {
        return response()->json(['error' => 'Evaluación no encontrada'], 404);
    }

    return response()->json($evaluacion);
}
    
}

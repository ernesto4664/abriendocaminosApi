<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use App\Models\Pregunta;
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
    
    public function index()
    {
        // Obtener todas las evaluaciones con sus preguntas y respuestas, sin ordenar
        $evaluaciones = Evaluacion::with([
            'preguntas.respuestas',
            'preguntas.respuestas.opciones',
            'preguntas.respuestas.opcionesLikert',
            'preguntas.respuestas.opcionesBarraSatisfaccion',
            'preguntas.respuestas.subpreguntas',
            'preguntas.respuestas.subpreguntas.opciones'
        ])
        ->get();
        
        // Verificar que cada pregunta tenga respuestas
        foreach ($evaluaciones as $evaluacion) {
            foreach ($evaluacion->preguntas as $pregunta) {
                if ($pregunta->respuestas->isEmpty()) {
                    // Asignamos una respuesta predeterminada si no hay respuestas
                    $respuesta = new Respuesta([
                        'respuesta' => 'Aún no se le han cargado posibles opciones de respuestas a esta pregunta',
                        'observaciones' => 'Sin observaciones',
                        'tipo' => 'Sin tipo'
                    ]);
                    $pregunta->respuestas = collect([$respuesta]);
                }
            }
        }
    
        // Retornar la estructura de datos
        return response()->json([
            'evaluaciones' => $evaluaciones
        ], 200);
    }
    
    
    /** 📌 Guardar varias respuestas para una pregunta */
    public function store(Request $request)
    {
        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'respuestas' => 'required|array',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
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
                    'respuesta' => $resp['respuesta'] ?? null,
                    'observaciones' => $resp['observaciones'] ?? null,
                    'tipo' => $resp['tipo']
                ]);

                // Guardar Opciones de Sí/No y otras simples
                if (!empty($resp['opciones']) && in_array($resp['tipo'], ['si_no', 'si_no_noestoyseguro', '5emojis'])) {
                    foreach ($resp['opciones'] as $opcion) {
                        RespuestaOpcion::create([
                            'respuesta_id' => $nuevaRespuesta->id,
                            'label' => $opcion['label'],
                            'valor' => $opcion['valor'] ?? null,
                        ]);
                    }
                }

                // Guardar Opciones de Barra de Satisfacción (0-10)
                if ($resp['tipo'] === 'barra_satisfaccion') {
                    for ($i = 0; $i <= 10; $i++) {
                        OpcionBarraSatisfaccion::create([
                            'respuesta_id' => $nuevaRespuesta->id,
                            'valor' => $i
                        ]);
                    }
                }

                // Guardar Subpreguntas del Likert con sus opciones
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

                // Incluir las relaciones en la respuesta
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

    /** 📌 Obtener una respuesta específica */
    public function show($id)
    {
        $respuesta = Respuesta::with(['opciones', 'opcionesBarraSatisfaccion', 'subpreguntas'])->find($id);

        if (!$respuesta) {
            return response()->json(['error' => 'Respuesta no encontrada'], 404);
        }

        return response()->json($respuesta, 200);
    }

    /** 📌 Actualizar respuestas */
    public function update(Request $request, $id)
    {
        \Log::info("Datos recibidos en update:", $request->all());

        // Buscar respuesta
        $respuesta = Respuesta::find($id);

        if (!$respuesta) {
            return response()->json(['error' => 'Respuesta no encontrada'], 404);
        }

        // Validación de entrada
        $request->validate([
            'tipo' => ['nullable', Rule::in(['texto', 'barra_satisfaccion', '5emojis', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero', 'opcion'])],
            'opciones' => 'nullable|array',
            'opciones.*.label' => 'required_with:opciones|string|max:255',
            'opciones.*.valor' => 'nullable|string|max:255',
            'subpreguntas' => 'nullable|array',
            'subpreguntas.*.texto' => 'required_with:subpreguntas|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            // Eliminar datos previos si el tipo cambia
            if ($respuesta->tipo !== $request->input('tipo')) {
                \Log::info("Cambiando tipo de respuesta de '{$respuesta->tipo}' a '{$request->input('tipo')}', eliminando datos previos.");

                // Eliminar opciones si el tipo cambia
                if (in_array($respuesta->tipo, ['si_no', 'si_no_noestoyseguro', '5emojis', 'barra_satisfaccion', 'opcion'])) {
                    $respuesta->opciones()->delete();
                    \Log::info("Opciones eliminadas para la respuesta ID {$respuesta->id}");
                }

                // Eliminar subpreguntas y opciones de tipo likert si el tipo cambia
                if ($respuesta->tipo === 'likert') {
                    foreach ($respuesta->subpreguntas as $subpregunta) {
                        OpcionLikert::where('subpregunta_id', $subpregunta->id)->delete();
                    }
                    \Log::info("Opciones Likert eliminadas para la respuesta ID {$respuesta->id}");

                    RespuestaSubpregunta::where('respuesta_id', $respuesta->id)->delete();
                    \Log::info("Subpreguntas eliminadas para la respuesta ID {$respuesta->id}");
                }
            }

            // Actualización del tipo de respuesta
            $respuesta->update([
                'tipo' => $request->input('tipo', $respuesta->tipo)
            ]);

            // Actualizar opciones si existen
            if ($request->has('opciones') && is_array($request->opciones)) {
                $respuesta->opciones()->delete();
                foreach ($request->opciones as $opcion) {
                    RespuestaOpcion::create([
                        'respuesta_id' => $respuesta->id,
                        'label' => $opcion['label'],
                        'valor' => $opcion['valor'] ?? null,
                    ]);
                }
            }

            // Actualizar subpreguntas si existen
            if ($request->has('subpreguntas') && is_array($request->subpreguntas)) {
                $respuesta->subpreguntas()->delete();
                foreach ($request->subpreguntas as $subpregunta) {
                    RespuestaSubpregunta::create([
                        'respuesta_id' => $respuesta->id,
                        'texto' => $subpregunta['texto']
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Respuesta actualizada con éxito',
                'respuesta' => $respuesta->load(['opciones', 'subpreguntas'])
            ], 200);
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

        DB::beginTransaction();
        try {
            // Eliminar opciones asociadas
            $respuesta->opciones()->delete();

            // Eliminar subpreguntas y sus opciones (si existen)
            foreach ($respuesta->subpreguntas as $subpregunta) {
                OpcionLikert::where('subpregunta_id', $subpregunta->id)->delete();
            }
            RespuestaSubpregunta::where('respuesta_id', $respuesta->id)->delete();

            // Eliminar la respuesta principal
            $respuesta->delete();

            DB::commit();
            return response()->json(['message' => 'Respuesta eliminada con éxito'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => 'Error al eliminar respuesta', 'details' => $e->getMessage()], 500);
        }
    }

    /** 📌 Obtener evaluacion completa */
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

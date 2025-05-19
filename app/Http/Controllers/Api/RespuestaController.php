<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use App\Models\RespuestaOpcion;
use App\Models\OpcionBarraSatisfaccion;
use App\Models\RespuestaSubpregunta;
use App\Models\RespuestaOpcionGlobal;
use App\Models\OpcionLikert;
use App\Models\RespuestaTipo;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class RespuestaController extends Controller
{
    /**
     * Obtener todas las evaluaciones con sus preguntas y respuestas
     */
    public function index()
    {
        try {
            $evaluaciones = Evaluacion::with([
                'preguntas.respuestas.opciones',
                'preguntas.respuestas.subpreguntas.opciones',
                'preguntas.respuestas.opcionesBarraSatisfaccion',
                'preguntas.respuestas.opcionesLikert',
                'preguntas.tiposDeRespuesta'
            ])->get();

            return response()->json($evaluaciones, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Respuesta][index] ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener evaluaciones'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    // Dentro de RespuestaController

    public function store(Request $request)
    {
        Log::info('[Respuesta][store] Recibiendo datos', $request->all());

        $request->validate([
            'evaluacion_id'            => 'required|exists:evaluaciones,id',
            'respuestas'               => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.tipo'        => ['required', Rule::in([
                'texto','numero','barra_satisfaccion','5emojis',
                'si_no','si_no_noestoyseguro','likert',
                'opcion','opcion_personalizada'
            ])],
            'respuestas.*.observaciones' => 'nullable|string',
            'respuestas.*.respuesta'     => 'nullable|string',
            'respuestas.*.opciones'      => 'nullable|array',
            'respuestas.*.subpreguntas'  => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            $guardadas = [];

            foreach ($request->respuestas as $r) {
                // 1) Crear la respuesta
                $valor = in_array($r['tipo'], ['texto','numero'])
                    ? ($r['respuesta'] ?? '')
                    : ($r['respuesta'] ?? null);

                $resp = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id'   => $r['pregunta_id'],
                    'respuesta'     => $valor,
                    'observaciones' => $r['observaciones'] ?? null,
                ]);

                RespuestaTipo::create([
                    'pregunta_id' => $r['pregunta_id'],
                    'tipo'        => $r['tipo'],
                ]);

                // 2) Opciones genéricas (sí/no, emojis, personalizadas)
                if (!empty($r['opciones'])) {
                    foreach ($r['opciones'] as $opt) {
                        RespuestaOpcion::create([
                            'respuesta_id' => $resp->id,
                            'label'        => $opt['label'] ?? null,
                            'valor'        => $opt['valor']  ?? null,
                        ]);
                    }
                }

                // 3) Barra de satisfacción
                if ($r['tipo'] === 'barra_satisfaccion') {
                    // Tu helper genera 0–10
                    $this->guardarBarraSatisfaccion($resp);
                }

                // 4) Likert
                if ($r['tipo'] === 'likert' && !empty($r['subpreguntas'])) {
                    foreach ($r['subpreguntas'] as $sp) {
                        $sub = RespuestaSubpregunta::create([
                            'respuesta_id' => $resp->id,
                            'texto'        => $sp['texto'],
                        ]);

                        // *** Aquí incluimos respuesta_id ***
                        foreach ($sp['opciones'] as $optLik) {
                            OpcionLikert::create([
                                'subpregunta_id' => $sub->id,
                                'respuesta_id'   => $resp->id,
                                'label'          => $optLik['label'],
                            ]);
                        }
                    }
                }

                $guardadas[] = $resp;
            }

            DB::commit();
            return response()->json($guardadas, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Respuesta][store] Error: ' . $e->getMessage());
            return response()->json(['message' => 'Error al guardar respuestas'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


public function updateMultiple(Request $request)
{
    Log::info('📥 [UPDATE MULTIPLE] Datos recibidos:', $request->all());

    $request->validate([
        'evaluacion_id'               => 'required|exists:evaluaciones,id',
        'respuestas'                  => 'required|array|min:1',
        'respuestas.*.pregunta_id'    => 'required|exists:preguntas,id',
        'respuestas.*.tipo'           => ['required', Rule::in([
            'texto','numero','barra_satisfaccion','5emojis',
            'si_no','si_no_noestoyseguro','likert',
            'opcion_personalizada'
        ])],
        'respuestas.*.observaciones'  => 'nullable|string',
        'respuestas.*.respuesta'      => 'nullable|string',
        'respuestas.*.opciones'       => 'nullable|array',
        'respuestas.*.opciones.*.label' => 'required_with:respuestas.*.opciones|string',
        'respuestas.*.opciones.*.valor' => 'nullable|string',
        'respuestas.*.subpreguntas'   => 'nullable|array',
    ]);

    DB::beginTransaction();

    try {
        foreach ($request->respuestas as $d) {
            // 1) Borrar viejas respuestas y dependencias
            $viejas = Respuesta::where('evaluacion_id', $request->evaluacion_id)
                ->where('pregunta_id', $d['pregunta_id'])
                ->get();

            foreach ($viejas as $old) {
                // eliminar todas las opciones de esa respuesta
                RespuestaOpcion::where('respuesta_id', $old->id)->delete();
                OpcionBarraSatisfaccion::where('respuesta_id', $old->id)->delete();

                // eliminar likerts y subpreguntas
                RespuestaSubpregunta::where('respuesta_id', $old->id)
                    ->each(function($sp){
                        OpcionLikert::where('subpregunta_id', $sp->id)->delete();
                        $sp->delete();
                    });

                $old->delete();
            }

            // eliminar el tipo anterior
            RespuestaTipo::where('pregunta_id', $d['pregunta_id'])->delete();

            // 2) Crear nueva Respuesta
            $resp = Respuesta::create([
                'evaluacion_id' => $request->evaluacion_id,
                'pregunta_id'   => $d['pregunta_id'],
                'respuesta'     => in_array($d['tipo'], ['texto','numero'])
                                    ? ($d['respuesta'] ?? '')
                                    : null,
                'observaciones' => $d['observaciones'] ?? null,
            ]);

            // grabar su tipo
            RespuestaTipo::create([
                'pregunta_id' => $d['pregunta_id'],
                'tipo'        => $d['tipo'],
            ]);

            // 3) Crear opciones de respuesta (sólo si vienen)
            if (!empty($d['opciones'])) {
                foreach ($d['opciones'] as $opt) {
                    RespuestaOpcion::create([
                        'respuesta_id' => $resp->id,            // ← aquí la FK correcta
                        'label'        => $opt['label'],
                        'valor'        => $opt['valor'] ?? null,
                    ]);
                }
            }

            // 4) Si es barra de satisfacción, grabamos el valor
            if ($d['tipo'] === 'barra_satisfaccion') {
                OpcionBarraSatisfaccion::create([
                    'respuesta_id' => $resp->id,
                    'valor'        => $d['respuesta'] ?? 0,
                ]);
            }

            // 5) Si es likert, grabar subpreguntas y opcionesLikert
            if ($d['tipo'] === 'likert' && !empty($d['subpreguntas'])) {
                foreach ($d['subpreguntas'] as $sp) {
                    $sub = RespuestaSubpregunta::create([
                        'respuesta_id' => $resp->id,
                        'texto'        => $sp['texto'],
                    ]);

                    foreach ($sp['opciones'] as $opLik) {
                        OpcionLikert::create([
                            'subpregunta_id' => $sub->id,
                            'respuesta_id'   => $resp->id,
                            'label'          => $opLik['label'],
                        ]);
                    }
                }
            }
        }

        DB::commit();
        return response()->json(['message' => 'Actualizado'], Response::HTTP_OK);

    } catch (\Throwable $e) {
        DB::rollBack();
        Log::error('[UPDATE MULTIPLE] Error: ' . $e->getMessage());
        return response()->json([
            'message' => 'Error al actualizar respuestas'
        ], Response::HTTP_INTERNAL_SERVER_ERROR);
    }
}

public function getEvaluacionCompleta($evaluacionId)
{
    $evaluacion = Evaluacion::with([
        'preguntas.tiposDeRespuesta',
        'preguntas.respuestas.tipoRespuesta',
        'preguntas.respuestas.opciones',
        'preguntas.respuestas.opcionesBarraSatisfaccion',
        'preguntas.respuestas.subpreguntas.opcionesLikert',
    ])->findOrFail($evaluacionId);

    $preguntas = $evaluacion->preguntas->map(function($pregunta) {
        $tipos = $pregunta->tiposDeRespuesta->pluck('tipo')->all();

        $resps = $pregunta->respuestas->map(function($resp) {
            // mapeo original de opciones
            $opcs = $resp->opciones->map(fn($o) => [
                'id'    => $o->id,
                'label' => $o->label,
                'valor' => $o->valor,
            ])->values();

            // si no hay opciones en BD, cargo defaults según el tipo
            if ($opcs->isEmpty()) {
                switch ($resp->tipoRespuesta->tipo) {
                    case 'si_no':
                        $opcs = collect([
                            ['id'=>null, 'label'=>'Sí', 'valor'=>'si'],
                            ['id'=>null, 'label'=>'No', 'valor'=>'no'],
                        ]);
                        break;

                    case 'si_no_noestoyseguro':
                        $opcs = collect([
                            ['id'=>null, 'label'=>'Sí',               'valor'=>'si'],
                            ['id'=>null, 'label'=>'No',               'valor'=>'no'],
                            ['id'=>null, 'label'=>'No estoy seguro',  'valor'=>'no_estoy_seguro'],
                        ]);
                        break;
                }
            }

            return [
                'id'            => $resp->id,
                'tipo'          => $resp->tipoRespuesta->tipo,
                'valor'         => $resp->respuesta ?? '',
                'observaciones' => $resp->observaciones,
                'opciones'      => $opcs,
                'subpreguntas'  => $resp->subpreguntas->map(fn($sp) => [
                    'id'       => $sp->id,
                    'texto'    => $sp->texto,
                    'opciones' => $sp->opcionesLikert->map(fn($ol) => [
                        'id'    => $ol->id,
                        'label' => $ol->label,
                        'valor' => null,
                    ])->values(),
                ])->values(),
            ];
        });

        if ($resps->isEmpty()) {
            // placeholder si no hay respuestas
            $resps = collect([[
                'id'            => null,
                'tipo'          => $tipos[0] ?? null,
                'valor'         => '',
                'observaciones' => null,
                'opciones'      => [],
                'subpreguntas'  => [],
            ]]);
        }

        return [
            'id'                 => $pregunta->id,
            'pregunta_id'        => $pregunta->id,
            'pregunta'           => $pregunta->pregunta,
            'tipos_de_respuesta' => array_map(fn($t) => ['tipo' => $t], $tipos),
            'respuestas'         => $resps->values(),
        ];
    })->values();

    return response()->json([
        'nombre'    => $evaluacion->nombre,
        'preguntas' => $preguntas,
    ]);
}




    private function guardarBarraSatisfaccion(Respuesta $r): void
    {
        $vals = range(0, 10);
        $data = array_map(fn($i) => [
            'respuesta_id' => $r->id,
            'valor'        => $i,
        ], $vals);

        OpcionBarraSatisfaccion::insert($data);
    }

    /**
     * Generar subpreguntas y opciones Likert
     */
    private function guardarSubpreguntasLikert(Respuesta $r, array $subs): void
    {
        foreach ($subs as $sp) {
            $sub = RespuestaSubpregunta::create([
                'respuesta_id' => $r->id,
                'texto'        => $sp['texto'],
            ]);

            $opts = array_map(fn($o) => [
                'subpregunta_id' => $sub->id,
                'label'          => $o['label'],
                'respuesta_id'   => $r->id,
            ], $sp['opciones']);

            OpcionLikert::insert($opts);
        }
    }

    /**
     * Guardar opciones de respuesta personalizadas
     */
    private function guardarOpciones(Respuesta $r, array $opts): void
    {
        $data = array_map(fn($o) => [
            'respuesta_id' => $r->id,
            'label'        => $o['label']  ?? null,
            'valor'        => $o['valor']  ?? null,
        ], $opts);

        RespuestaOpcion::insert($data);
    }

    public function destroy($id)
    {
        $respuesta = Respuesta::find($id);
        if (! $respuesta) {
            return response()->json([
                'message' => 'Respuesta no encontrada.'
            ], Response::HTTP_NOT_FOUND);
        }

        $respuesta->delete();  // elimina la fila (y cascada si lo tienes configurado)
        return response()->json([
            'message' => 'Respuesta eliminada correctamente.'
        ], Response::HTTP_OK);
    }

    public function destroyPorPregunta($preguntaId)
    {
        DB::beginTransaction();
        try {
            // IDs de respuestas (si acaso hubiera)
            $respuestaIds = Respuesta::where('pregunta_id', $preguntaId)->pluck('id');

            // 1) Opciones genéricas (tu modelo RespuestaOpcion)
            RespuestaOpcion::whereIn('respuesta_id', $respuestaIds)->delete();

            // 2) Barra de satisfacción
            OpcionBarraSatisfaccion::whereIn('respuesta_id', $respuestaIds)->delete();

            // 3) Likert: primero sus subpreguntas…
            $subIds = RespuestaSubpregunta::whereIn('respuesta_id', $respuestaIds)->pluck('id');
            OpcionLikert::whereIn('subpregunta_id', $subIds)->delete();
            RespuestaSubpregunta::whereIn('id', $subIds)->delete();

            // 4) Tipo de respuesta
            RespuestaTipo::where('pregunta_id', $preguntaId)->delete();

            // 5) Finalmente, las respuestas
            $count = Respuesta::where('pregunta_id', $preguntaId)->delete();

            DB::commit();
            return response()->json([
                'message' => "Eliminadas opciones y $count respuestas."
            ], Response::HTTP_OK);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['error' => 'Falló la limpieza'], 500);
        }
    }

    public function limpiarPreguntaCompleta($preguntaId, $evaluacionId)
    {
        DB::beginTransaction();

        try {
            // 1) Borrar detalles de Ponderación para ESTA pregunta
            DetallePonderacion::where(compact('preguntaId'))
                ->whereHas('cabecera', fn($q) => $q->where('evaluacion_id', $evaluacionId))
                ->delete();

            // 2) (Opcional) Si una cabecera no tiene más detalles, la borras:
            Ponderacion::where('evaluacion_id', $evaluacionId)
                ->doesntHave('detalles')
                ->delete();

            // 3) Borrar respuestas y dependencias
            // reutilizas tu método destroyPorPregunta internamente:
            app(RespuestaController::class)->destroyPorPregunta($preguntaId);

            DB::commit();
            return response()->json(['message' => 'Limpieza completa exitosa.'], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[Respuesta][limpiarPreguntaCompleta] '.$e->getMessage());
            return response()->json(['error'=>'No se pudo limpiar la pregunta'], 500);
        }
    }
}
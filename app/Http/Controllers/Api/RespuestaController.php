<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use App\Models\RespuestaOpcion;
use App\Models\OpcionBarraSatisfaccion;
use App\Models\RespuestaSubpregunta;
use App\Models\OpcionLikert;
use App\Models\RespuestaTipo;
use App\Models\Evaluacion;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

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

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $evaluaciones,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('❌ [INDEX] Error al listar evaluaciones: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error interno al obtener evaluaciones',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Guardar varias respuestas para una evaluación
     */
    public function store(Request $request)
    {
        Log::info('📡 [STORE] Recibiendo respuestas:', $request->all());

        $request->validate([
            'evaluacion_id'            => 'required|exists:evaluaciones,id',
            'respuestas'               => 'required|array|min:1',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.tipo'        => ['required', Rule::in([
                'texto','barra_satisfaccion','5emojis',
                'si_no','si_no_noestoyseguro','likert',
                'numero','opcion','opcion_personalizada'
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
                $valor = in_array($r['tipo'], ['texto','numero']) ? '' : ($r['respuesta'] ?? null);

                $respuesta = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id'   => $r['pregunta_id'],
                    'respuesta'     => $valor,
                    'observaciones' => $r['observaciones'] ?? null,
                ]);

                RespuestaTipo::create([
                    'pregunta_id' => $r['pregunta_id'],
                    'tipo'        => $r['tipo'],
                ]);

                if (!empty($r['opciones']) && in_array($r['tipo'], [
                    'opcion_personalizada','si_no','si_no_noestoyseguro','5emojis'
                ])) {
                    foreach ($r['opciones'] as $opt) {
                        RespuestaOpcion::create([
                            'respuesta_id' => $respuesta->id,
                            'label'        => $opt,
                        ]);
                    }
                }

                if ($r['tipo'] === 'barra_satisfaccion') {
                    OpcionBarraSatisfaccion::create([
                        'respuesta_id' => $respuesta->id,
                    ]);
                }

                if ($r['tipo'] === 'likert' && !empty($r['subpreguntas'])) {
                    foreach ($r['subpreguntas'] as $sp) {
                        $sub = RespuestaSubpregunta::create([
                            'respuesta_id' => $respuesta->id,
                            'texto'        => $sp['texto'],
                        ]);
                        foreach ($sp['opcionesLikert'] as $lik) {
                            OpcionLikert::create([
                                'subpregunta_id' => $sub->id,
                                'label'          => $lik,
                            ]);
                        }
                    }
                }

                $guardadas[] = $respuesta;
            }

            DB::commit();

            return response()->json([
                'code'    => Response::HTTP_CREATED,
                'message' => 'Respuestas guardadas con éxito',
                'data'    => $guardadas,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al guardar respuestas: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al guardar respuestas',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar múltiples respuestas en una evaluación
     */
    public function updateMultiple(Request $request)
    {
        Log::info('📥 [UPDATE MULTIPLE] Datos recibidos:', $request->all());

        $request->validate([
            'evaluacion_id'            => 'required|exists:evaluaciones,id',
            'respuestas'               => 'required|array|min:1',
            'respuestas.*.id'          => 'nullable|exists:respuestas,id',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.tipo'        => ['required', Rule::in([
                'texto','barra_satisfaccion','5emojis',
                'si_no','si_no_noestoyseguro','likert',
                'numero','opcion','opcion_personalizada'
            ])],
            'respuestas.*.observaciones' => 'nullable|string',
            'respuestas.*.respuesta'     => 'nullable|string',
            'respuestas.*.opciones'      => 'nullable|array',
            'respuestas.*.subpreguntas'  => 'nullable|array',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->respuestas as $d) {
                // Eliminar antiguas respuestas y dependencias
                $old = Respuesta::where('evaluacion_id', $request->evaluacion_id)
                    ->where('pregunta_id', $d['pregunta_id'])
                    ->get();
                foreach ($old as $o) {
                    RespuestaOpcion::where('respuesta_id', $o->id)->delete();
                    OpcionBarraSatisfaccion::where('respuesta_id', $o->id)->delete();
                    RespuestaSubpregunta::where('respuesta_id', $o->id)->each(function ($sp) {
                        OpcionLikert::where('subpregunta_id', $sp->id)->delete();
                        $sp->delete();
                    });
                    $o->delete();
                }
                RespuestaTipo::where('pregunta_id', $d['pregunta_id'])->delete();

                // Crear nueva respuesta
                $resp = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id'   => $d['pregunta_id'],
                    'respuesta'     => $d['respuesta'] ?? null,
                    'observaciones' => $d['observaciones'] ?? null,
                ]);
                RespuestaTipo::create([
                    'pregunta_id' => $d['pregunta_id'],
                    'tipo'        => $d['tipo'],
                ]);

                if (!empty($d['opciones'])) {
                    foreach ($d['opciones'] as $opt) {
                        RespuestaOpcion::create([
                            'respuesta_id' => $resp->id,
                            'label'        => $opt,
                        ]);
                    }
                }
                if ($d['tipo'] === 'barra_satisfaccion') {
                    OpcionBarraSatisfaccion::create(['respuesta_id' => $resp->id]);
                }
                if ($d['tipo'] === 'likert' && !empty($d['subpreguntas'])) {
                    foreach ($d['subpreguntas'] as $sp) {
                        $sub = RespuestaSubpregunta::create([
                            'respuesta_id' => $resp->id,
                            'texto'        => $sp['texto'],
                        ]);
                        foreach ($sp['opcionesLikert'] as $lik) {
                            OpcionLikert::create([
                                'subpregunta_id' => $sub->id,
                                'label'          => $lik,
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Respuestas actualizadas correctamente',
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE MULTIPLE] Error: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al actualizar respuestas',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEvaluacionCompleta($evaluacion_id)
    {
        Log::info("📥 [GET COMPLETA] evaluacion_id={$evaluacion_id}");

        try {
            $evaluacion = Evaluacion::with([
                'preguntas.respuestas.opciones',
                'preguntas.respuestas.subpreguntas.opciones',
                'preguntas.respuestas.opcionesBarraSatisfaccion',
                'preguntas.respuestas.opcionesLikert',
                'preguntas.tiposDeRespuesta'
            ])->findOrFail($evaluacion_id);

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $evaluacion,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("⚠️ [GET COMPLETA] Evaluación ID={$evaluacion_id} no encontrada");

            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Evaluación no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('❌ [GET COMPLETA] Error al obtener evaluación completa: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener evaluación completa',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generar opciones de barra de satisfacción (0-10)
     */
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

}
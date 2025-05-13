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
use App\Models\MDSFApiResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RespuestaController extends Controller
{
    /** 📌 Obtener todas las evaluaciones con preguntas y respuestas */
    public function index()
    {
        $resp = new MDSFApiResponse();
        try {
            $evaluaciones = Evaluacion::with([
                'preguntas' => function ($q) {
                    $q->with([
                        'respuestas' => function ($q2) {
                            $q2->with([
                                'opciones',
                                'subpreguntas.opciones',
                                'opcionesBarraSatisfaccion',
                                'opcionesLikert'
                            ]);
                        },
                        'tiposDeRespuesta'
                    ]);
                }
            ])->get();

            $resp->data = $evaluaciones;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('❌ [INDEX] Error al listar evaluaciones: ' . $e->getMessage());
            $resp->code = 500;
            $resp->message = 'Error interno al obtener evaluaciones';
            $resp->error = $e->getMessage();
        }

        return $resp->json();
    }

    /** 📌 Guardar varias respuestas para una evaluación */
    public function store(Request $request)
    {
        $resp = new MDSFApiResponse();
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

                $n = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id'   => $r['pregunta_id'],
                    'respuesta'     => $valor,
                    'observaciones' => $r['observaciones'] ?? null,
                ]);

                RespuestaTipo::create([
                    'pregunta_id' => $r['pregunta_id'],
                    'tipo'        => $r['tipo']
                ]);

                if (!empty($r['opciones']) && in_array($r['tipo'], [
                    'opcion_personalizada','si_no','si_no_noestoyseguro','5emojis'
                ])) {
                    $this->guardarOpciones($n, $r['opciones']);
                }
                if ($r['tipo'] === 'barra_satisfaccion') {
                    $this->guardarBarraSatisfaccion($n);
                }
                if ($r['tipo'] === 'likert' && !empty($r['subpreguntas'])) {
                    $this->guardarSubpreguntasLikert($n, $r['subpreguntas']);
                }

                $guardadas[] = $n;
            }

            DB::commit();
            $resp->data = $guardadas;
            $resp->message = 'Respuestas guardadas con éxito';
            $resp->code = 201;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al guardar respuestas: ' . $e->getMessage());
            $resp->code = 500;
            $resp->message = 'Error al guardar respuestas';
            $resp->error = $e->getMessage();
        }

        return $resp->json();
    }

    /** 📌 Actualizar múltiples respuestas en una evaluación */
    public function updateMultiple(Request $request)
    {
        $resp = new MDSFApiResponse();
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
                // eliminar antiguas
                Respuesta::where('evaluacion_id', $request->evaluacion_id)
                    ->where('pregunta_id', $d['pregunta_id'])
                    ->each(function($old) {
                        RespuestaOpcion::where('respuesta_id', $old->id)->delete();
                        OpcionBarraSatisfaccion::where('respuesta_id', $old->id)->delete();
                        RespuestaSubpregunta::where('respuesta_id', $old->id)
                            ->each(function($sp) {
                                OpcionLikert::where('subpregunta_id', $sp->id)->delete();
                                $sp->delete();
                            });
                        $old->delete();
                    });
                RespuestaTipo::where('pregunta_id', $d['pregunta_id'])->delete();

                // crear nueva
                $n = Respuesta::create([
                    'evaluacion_id' => $request->evaluacion_id,
                    'pregunta_id'   => $d['pregunta_id'],
                    'respuesta'     => $d['respuesta'] ?? null,
                    'observaciones' => $d['observaciones'] ?? null,
                ]);
                RespuestaTipo::create([
                    'pregunta_id' => $d['pregunta_id'],
                    'tipo'        => $d['tipo']
                ]);

                if (!empty($d['opciones'])) {
                    $this->guardarOpciones($n, $d['opciones']);
                }
                if ($d['tipo'] === 'barra_satisfaccion') {
                    $this->guardarBarraSatisfaccion($n);
                }
                if ($d['tipo'] === 'likert' && !empty($d['subpreguntas'])) {
                    $this->guardarSubpreguntasLikert($n, $d['subpreguntas']);
                }
            }

            DB::commit();
            $resp->code    = 200;
            $resp->message = 'Respuestas actualizadas correctamente';
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE MULTIPLE] Error: ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al actualizar respuestas';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /** 📌 Obtener evaluación completa con preguntas y respuestas */
    public function getEvaluacionCompleta($evaluacion_id)
    {
        $resp = new MDSFApiResponse();
        Log::info("📥 [GET COMPLETA] evaluacion_id={$evaluacion_id}");

        try {
            $evaluacion = Evaluacion::with([
                'preguntas.respuestas.opciones',
                'preguntas.respuestas.subpreguntas.opciones',
                'preguntas.respuestas.opcionesBarraSatisfaccion',
                'preguntas.respuestas.opcionesLikert',
                'preguntas.tiposDeRespuesta'
            ])->findOrFail($evaluacion_id);

            $resp->data = $evaluacion;
            $resp->code = 200;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            Log::warning("⚠️ [GET COMPLETA] No encontrada ID={$evaluacion_id}");
            $resp->code    = 404;
            $resp->message = 'Evaluación no encontrada';
        } catch (\Exception $e) {
            Log::error('❌ [GET COMPLETA] Error: ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener evaluación completa';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /** 📌 Helpers internos **/

  /*  private function guardarOpciones(Respuesta $r, array $opts)
    {
        foreach ($opts as $o) {
            RespuestaOpcion::create([
                'respuesta_id' => $r->id,
                'label'        => $o['label'] ?? null,
                'valor'        => $o['valor'] ?? null,
            ]);
        }
    }*/

    private function guardarBarraSatisfaccion(Respuesta $r)
    {
        for ($i = 0; $i <= 10; $i++) {
            OpcionBarraSatisfaccion::create([
                'respuesta_id' => $r->id,
                'valor'        => $i,
            ]);
        }
    }

    private function guardarSubpreguntasLikert(Respuesta $r, array $subs)
    {
        foreach ($subs as $sp) {
            $n = RespuestaSubpregunta::create([
                'respuesta_id' => $r->id,
                'texto'        => $sp['texto'],
            ]);
            foreach ($sp['opciones'] as $o) {
                OpcionLikert::create([
                    'subpregunta_id' => $n->id,
                    'label'          => $o['label'],
                    'respuesta_id'   => $r->id,
                ]);
            }
        }
    }


 /** 📌 Actualizar múltiples respuestas en una evaluación */
 /**public function updateMultiple(Request $request)
 {
     $resp = new MDSFApiResponse();
     Log::info("📥 [UPDATE MULTIPLE] Datos recibidos:", $request->all());

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
             // eliminar anteriores
             Respuesta::where('evaluacion_id', $request->evaluacion_id)
                 ->where('pregunta_id', $d['pregunta_id'])
                 ->get()
                 ->each(function($old) {
                     RespuestaOpcion::where('respuesta_id',$old->id)->delete();
                     OpcionBarraSatisfaccion::where('respuesta_id',$old->id)->delete();
                     RespuestaSubpregunta::where('respuesta_id',$old->id)
                         ->each(function($sp){
                             OpcionLikert::where('subpregunta_id',$sp->id)->delete();
                             $sp->delete();
                         });
                     $old->delete();
                 });

             RespuestaTipo::where('pregunta_id',$d['pregunta_id'])->delete();

             // crear nueva
             $n = Respuesta::create([
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
                 $this->guardarOpciones($n, $d['opciones']);
             }
             if ($d['tipo']==='barra_satisfaccion') {
                 $this->guardarBarraSatisfaccion($n);
             }
             if ($d['tipo']==='likert' && !empty($d['subpreguntas'])) {
                 $this->guardarSubpreguntasLikert($n, $d['subpreguntas']);
             }
         }

         DB::commit();
         $resp->code    = 200;
         $resp->message = 'Respuestas actualizadas correctamente';
     } catch (\Exception $e) {
         DB::rollBack();
         Log::error("❌ [UPDATE MULTIPLE] Error: ".$e->getMessage());
         $resp->code    = 500;
         $resp->message = 'Error al actualizar respuestas';
         $resp->error   = $e->getMessage();
     }

     return $resp->json();
 }**/

 /** 🔧 Helpers internos **/
 private function guardarOpciones(Respuesta $r, array $opts)
 {
     foreach ($opts as $o) {
         RespuestaOpcion::create([
             'respuesta_id' => $r->id,
             'label'        => $o['label'] ?? null,
             'valor'        => $o['valor'] ?? null,
         ]);
     }
 }

/* private function guardarBarraSatisfaccion(Respuesta $r)
 {
     for ($i=0; $i<=10; $i++){
         OpcionBarraSatisfaccion::create([
             'respuesta_id' => $r->id,
             'valor'        => $i,
         ]);
     }
 }*/

/* private function guardarSubpreguntasLikert(Respuesta $r, array $subs)
 {
     foreach ($subs as $sp){
         $n = RespuestaSubpregunta::create([
             'respuesta_id' => $r->id,
             'texto'        => $sp['texto'],
         ]);
         foreach ($sp['opciones'] as $o) {
             OpcionLikert::create([
                 'subpregunta_id' => $n->id,
                 'label'          => $o['label'],
                 'respuesta_id'   => $r->id,
             ]);
         }
     }*/
 }


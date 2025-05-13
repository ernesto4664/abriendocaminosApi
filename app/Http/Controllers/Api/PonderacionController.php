<?php

namespace App\Http\Controllers\Api;
use App\Models\MDSFApiResponse;
use App\Http\Controllers\Controller;
use App\Models\Ponderacion;
use App\Models\DetallePonderacion;
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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PonderacionController extends Controller
{
    public function store(Request $request)
    {
        // Inicializar respuesta estandarizada
        $resp = new MDSFApiResponse();
        Log::info('[PONDERACION][STORE] Inicio de validación', $request->all());
    
        // Reglas base
        $rules = [
            'plan_id'       => 'required|integer|exists:planes_intervencion,id',
            'evaluacion_id' => 'required|integer|exists:evaluaciones,id',
            'detalles'      => 'required|array|min:1',
            'detalles.*.pregunta_id'        => 'required|integer|exists:preguntas,id',
            'detalles.*.tipo'               => 'required|string|in:texto,numero,barra_satisfaccion,si_no,5emojis,opcion_personalizada,si_no_noestoyseguro,likert',
            'detalles.*.valor'              => 'required|numeric|min:0',
            'detalles.*.respuesta_correcta' => 'required_if:detalles.*.tipo,texto|string|max:500',
        ];
    
        $validator = Validator::make($request->all(), $rules);
    
        // Para los tipos que usan opciones, siempre validamos que el ID exista en respuestas_opciones
        $validator->sometimes('detalles.*.respuesta_correcta_id', 'required|integer|exists:respuestas_opciones,id', function ($input, $detalle) {
            return in_array($detalle->tipo, [
                'si_no',
                'si_no_noestoyseguro',
                '5emojis',
                'opcion_personalizada'
            ]);
        });
    
        // Para likert usamos la tabla de opciones_likert
        $validator->sometimes('detalles.*.respuesta_correcta_id', 'required|integer|exists:opciones_likert,id', function ($input, $detalle) {
            return $detalle->tipo === 'likert';
        });
    
        // Y validamos la subpregunta sólo para likert (tabla respuestas_subpreguntas)
        $validator->sometimes('detalles.*.subpregunta_id', 'required|integer|exists:respuestas_subpreguntas,id', function ($input, $detalle) {
            return $detalle->tipo === 'likert';
        });
    
        if ($validator->fails()) {
            Log::warning('[PONDERACION][STORE] Validación fallida', $validator->errors()->toArray());
            $resp->code = 422;
            $resp->message = 'Errores en la validación';
            $resp->errors = $validator->errors();
            return $resp->json();
        }
    
        Log::info('[PONDERACION][STORE] Validación OK, comenzando transacción');
        DB::beginTransaction();
    
        try {
            $ponderacion = Ponderacion::create([
                'plan_id'       => $request->plan_id,
                'evaluacion_id' => $request->evaluacion_id,
                'user_id'       => auth()->id(),
            ]);
            Log::info('[PONDERACION][STORE] Cabecera creada', ['id' => $ponderacion->id]);
    
            foreach ($request->detalles as $det) {
                $detalle = new DetallePonderacion([
                    'pregunta_id'           => $det['pregunta_id'],
                    'tipo'                  => $det['tipo'],
                    'valor'                 => $det['valor'],
                    'respuesta_correcta'    => $det['tipo'] === 'texto'
                                                  ? $det['respuesta_correcta']
                                                  : null,
                    'respuesta_correcta_id' => in_array($det['tipo'], [
                                                  'si_no',
                                                  'si_no_noestoyseguro',
                                                  '5emojis',
                                                  'opcion_personalizada',
                                                  'likert'
                                                ])
                                                ? $det['respuesta_correcta_id']
                                                : null,
                    'subpregunta_id'        => $det['tipo'] === 'likert'
                                                  ? $det['subpregunta_id']
                                                  : null,
                ]);
    
                $ponderacion->detalles()->save($detalle);
                Log::info('[PONDERACION][STORE] Detalle guardado', [
                    'pregunta_id'    => $detalle->pregunta_id,
                    'subpregunta_id' => $detalle->subpregunta_id ?? 'n/a',
                    'tipo'           => $detalle->tipo,
                    'valor'          => $detalle->valor,
                ]);
            }
    
            DB::commit();
            Log::info('[PONDERACION][STORE] Commit exitoso');
    
            $resp->code = 201;
            $resp->message = 'Ponderaciones guardadas.';
            $resp->data = $ponderacion->load('detalles');
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PONDERACION][STORE] Rollback por error', ['error' => $e->getMessage()]);
            $resp->code = 500;
            $resp->message = 'Error al guardar ponderaciones.';
            $resp->errors = $e->getMessage();
        }
    
        return $resp->json();
    }
    
    public function completo()
    {
        // Inicializar respuesta estandarizada
        $resp = new MDSFApiResponse();
        Log::info('[PONDERACION][COMPLETO] Inicio');
    
        try {
            $all = Ponderacion::with([
                'evaluacion:id,nombre',
                'detalles.pregunta:id,pregunta',
                'detalles.subpregunta:id,texto',
                'detalles.respuestaOpcionCorrecta:id,label',
                'detalles.opcionLikertCorrecta:id,label',
            ])->get();
    
            $result = $all->map(function (Ponderacion $p) {
                return [
                    'id'                => $p->id,
                    'plan_id'           => $p->plan_id,
                    'evaluacion_id'     => $p->evaluacion_id,
                    'evaluacion_nombre' => $p->evaluacion->nombre,
                    'total_puntos'      => $p->detalles->sum('valor'),
                    'detalles'          => $p->detalles->map(function ($det) {
                        // elegimos el label correcto según el tipo
                        if (in_array($det->tipo, ['texto', 'numero'])) {
                            $label = $det->respuesta_correcta;
                        } elseif ($det->tipo === 'likert') {
                            $label = optional($det->opcionLikertCorrecta)->label;
                        } else {
                            $label = optional($det->respuestaOpcionCorrecta)->label;
                        }
    
                        return [
                            'id'                       => $det->id,
                            'pregunta_id'              => $det->pregunta_id,
                            'pregunta_texto'           => $det->pregunta->pregunta,
                            'tipo'                     => $det->tipo,
                            'valor'                    => $det->valor,
                            'respuesta_correcta_id'    => $det->respuesta_correcta_id,
                            'respuesta_correcta_label' => $label,
                            'subpregunta_id'           => $det->tipo === 'likert' ? $det->subpregunta_id : null,
                            'subpregunta_texto'        => $det->tipo === 'likert' ? optional($det->subpregunta)->texto : null,
                        ];
                    })->values(),
                ];
            });
    
            $resp->code = 200;
            $resp->data = $result;
        } catch (\Exception $e) {
            Log::error('[PONDERACION][COMPLETO] Error', ['error' => $e->getMessage()]);
            $resp->code = 500;
            $resp->message = 'Error al obtener las ponderaciones.';
            $resp->errors = $e->getMessage();
        }

        return $resp->json();
    }
}


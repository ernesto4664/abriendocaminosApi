<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ponderacion;
use App\Models\DetallePonderacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class PonderacionController extends Controller
{
    /**
     * Almacenar nueva ponderación con sus detalles
     */
    public function store(Request $request)
    {
        Log::info('[PONDERACION][STORE] Inicio de validación', $request->all());

        // Reglas de validación
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
        // Validaciones condicionales según tipo
        $validator->sometimes('detalles.*.respuesta_correcta_id', 'required|integer|exists:respuestas_opciones,id', function ($input, $detalle) {
            return in_array($detalle->tipo, ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada']);
        });
        $validator->sometimes('detalles.*.respuesta_correcta_id', 'required|integer|exists:opciones_likert,id', function ($input, $detalle) {
            return $detalle->tipo === 'likert';
        });
        $validator->sometimes('detalles.*.subpregunta_id', 'required|integer|exists:respuestas_subpreguntas,id', function ($input, $detalle) {
            return $detalle->tipo === 'likert';
        });

        if ($validator->fails()) {
            Log::warning('[PONDERACION][STORE] Validación fallida', $validator->errors()->toArray());
            return response()->json([
                'code'    => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'Errores en la validación',
                'errors'  => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

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
                    'respuesta_correcta'    => $det['tipo']==='texto' ? $det['respuesta_correcta'] : null,
                    'respuesta_correcta_id' => in_array($det['tipo'], ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada','likert'])
                        ? $det['respuesta_correcta_id'] : null,
                    'subpregunta_id'        => $det['tipo']==='likert' ? $det['subpregunta_id'] : null,
                ]);
                $ponderacion->detalles()->save($detalle);
                Log::info('[PONDERACION][STORE] Detalle guardado', ['pregunta_id'=>$detalle->pregunta_id,'tipo'=>$detalle->tipo,'valor'=>$detalle->valor]);
            }

            DB::commit();
            Log::info('[PONDERACION][STORE] Commit exitoso');

            return response()->json([
                'code'    => Response::HTTP_CREATED,
                'message' => 'Ponderaciones guardadas.',
                'data'    => $ponderacion->load('detalles'),
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PONDERACION][STORE] Rollback por error: '.$e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al guardar ponderaciones.',
                'errors'  => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener todas las ponderaciones con detalles enriquecidos
     */
    public function completo()
    {
        Log::info('[PONDERACION][COMPLETO] Inicio');

        try {
            $all = Ponderacion::with([
                'evaluacion:id,nombre',
                'detalles.pregunta:id,pregunta',
                'detalles.subpregunta:id,texto',
                'detalles.respuestaOpcionCorrecta:id,label',
                'detalles.opcionLikertCorrecta:id,label',
            ])->get();

            $result = $all->map(fn(Ponderacion $p) => [
                'id'                => $p->id,
                'plan_id'           => $p->plan_id,
                'evaluacion_id'     => $p->evaluacion_id,
                'evaluacion_nombre' => $p->evaluacion->nombre,
                'total_puntos'      => $p->detalles->sum('valor'),
                'detalles'          => $p->detalles->map(fn($det) => [
                    'id'                       => $det->id,
                    'pregunta_id'              => $det->pregunta_id,
                    'pregunta_texto'           => $det->pregunta->pregunta,
                    'tipo'                     => $det->tipo,
                    'valor'                    => $det->valor,
                    'respuesta_correcta_id'    => $det->respuesta_correcta_id,
                    'respuesta_correcta_label' => match($det->tipo) {
                        'texto','numero' => $det->respuesta_correcta,
                        'likert'         => optional($det->opcionLikertCorrecta)->label,
                        default          => optional($det->respuestaOpcionCorrecta)->label,
                    },
                    'subpregunta_id'           => $det->tipo==='likert' ? $det->subpregunta_id : null,
                    'subpregunta_texto'        => $det->tipo==='likert' ? optional($det->subpregunta)->texto : null,
                ])->values(),
            ]);

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $result,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[PONDERACION][COMPLETO] Error: '.$e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener las ponderaciones.',
                'errors'  => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

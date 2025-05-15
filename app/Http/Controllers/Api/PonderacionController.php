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

    public function store(Request $request)
    {
        Log::info('[PONDERACION][STORE] Inicio', $request->all());

        // 1) Reglas de validación
        $rules = [
            'plan_id'       => 'required|integer|exists:planes_intervencion,id',
            'evaluacion_id' => 'required|integer|exists:evaluaciones,id',
            'detalles'      => 'required|array|min:1',
            'detalles.*.pregunta_id'        => 'required|integer|exists:preguntas,id',
            'detalles.*.tipo'               => 'required|string|in:texto,numero,barra_satisfaccion,si_no,si_no_noestoyseguro,5emojis,opcion_personalizada,likert',
            // Validación de rango de la ponderación
            'detalles.*.valor'              => 'required|numeric|min:0|max:10',
            // Cuando el tipo es texto o número, necesitamos respuesta_correcta
            'detalles.*.respuesta_correcta' => 'required_if:detalles.*.tipo,texto,numero|string|max:500',
        ];

        $validator = Validator::make($request->all(), $rules);

        // 2) Validaciones condicionales según tipo
        // Opciones discretas y emojis
        $validator->sometimes(
            'detalles.*.respuesta_correcta_id',
            'required|integer|exists:respuestas_opciones,id',
            fn($input, $det) => in_array($det->tipo, ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada'], true)
        );
        // Likert
        $validator->sometimes(
            'detalles.*.respuesta_correcta_id',
            'required|integer|exists:opciones_likert,id',
            fn($input, $det) => $det->tipo === 'likert'
        );
        $validator->sometimes(
            'detalles.*.subpregunta_id',
            'required|integer|exists:respuestas_subpreguntas,id',
            fn($input, $det) => $det->tipo === 'likert'
        );

        if ($validator->fails()) {
            Log::warning('[PONDERACION][STORE] Falló validación', $validator->errors()->toArray());
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // 3) Persistencia atómica
        DB::beginTransaction();
        try {
            // Crear cabecera
            $ponderacion = Ponderacion::create([
                'plan_id'       => $request->plan_id,
                'evaluacion_id' => $request->evaluacion_id,
                'user_id'       => auth()->id(),
            ]);
            Log::info('[PONDERACION][STORE] Creada cabecera', ['id' => $ponderacion->id]);

            // Guardar cada detalle
            foreach ($request->detalles as $det) {
                $detalle = new DetallePonderacion([
                    'pregunta_id'           => $det['pregunta_id'],
                    'tipo'                  => $det['tipo'],
                    'valor'                 => $det['valor'],
                    // Solo texto/numero
                    'respuesta_correcta'    => in_array($det['tipo'], ['texto','numero'], true)
                        ? $det['respuesta_correcta']
                        : null,
                    // Discretas, emojis y likert
                    'respuesta_correcta_id' => in_array($det['tipo'], ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada','likert'], true)
                        ? $det['respuesta_correcta_id']
                        : null,
                    // Solo likert
                    'subpregunta_id'        => $det['tipo'] === 'likert'
                        ? $det['subpregunta_id']
                        : null,
                ]);

                $ponderacion->detalles()->save($detalle);
                Log::info('[PONDERACION][STORE] Guardado detalle', [
                    'pregunta_id' => $detalle->pregunta_id,
                    'tipo'        => $detalle->tipo,
                    'valor'       => $detalle->valor,
                ]);
            }

            DB::commit();
            Log::info('[PONDERACION][STORE] Commit exitoso');

            // 4) Devolver la ponderación con sus detalles
            $ponderacion->load('detalles');
            return response()->json($ponderacion, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PONDERACION][STORE] Rollback por error', ['message' => $e->getMessage()]);
            return response()->json([
                'error' => 'No se pudo guardar la ponderación'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
                    'subpregunta_id'           => $det->tipo === 'likert' ? $det->subpregunta_id : null,
                    'subpregunta_texto'        => $det->tipo === 'likert' ? optional($det->subpregunta)->texto : null,
                ])->values(),
            ]);

            return response()->json($result, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[PONDERACION][COMPLETO] Error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

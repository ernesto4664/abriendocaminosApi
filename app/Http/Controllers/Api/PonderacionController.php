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
        $validator->sometimes('detalles.*.respuesta_correcta_id', 'required|integer|exists:respuestas_opciones,id', fn($input, $det) => in_array($det->tipo, ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada']));
        $validator->sometimes('detalles.*.respuesta_correcta_id', 'required|integer|exists:opciones_likert,id', fn($input, $det) => $det->tipo === 'likert');
        $validator->sometimes('detalles.*.subpregunta_id', 'required|integer|exists:respuestas_subpreguntas,id', fn($input, $det) => $det->tipo === 'likert');

        if ($validator->fails()) {
           
            return response()->json($validator->errors(), Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        DB::beginTransaction();
        try {
            $ponderacion = Ponderacion::create([
                'plan_id'       => $request->plan_id,
                'evaluacion_id' => $request->evaluacion_id,
                'user_id'       => auth()->id(),
            ]);
            
            foreach ($request->detalles as $det) {
                $detalle = new DetallePonderacion([
                    'pregunta_id'           => $det['pregunta_id'],
                    'tipo'                  => $det['tipo'],
                    'valor'                 => $det['valor'],
                    'respuesta_correcta'    => $det['tipo'] === 'texto' ? $det['respuesta_correcta'] : null,
                    'respuesta_correcta_id' => in_array($det['tipo'], ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada','likert'])
                        ? $det['respuesta_correcta_id'] : null,
                    'subpregunta_id'        => $det['tipo'] === 'likert' ? $det['subpregunta_id'] : null,
                ]);
                $ponderacion->detalles()->save($detalle);
                Log::info('[PONDERACION][STORE] Detalle guardado', ['pregunta_id' => $detalle->pregunta_id, 'tipo' => $detalle->tipo, 'valor' => $detalle->valor]);
            }

            DB::commit();

            return response()->json($ponderacion->load('detalles'), Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('[PONDERACION][STORE] Rollback por error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function completo()
    {
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
                'detalles'          => $p->detalles
                    ->map(fn($det) => [
                        'id'                       => $det->id,
                        'pregunta_id'              => $det->pregunta_id,
                        'pregunta_texto'           => $det->pregunta->pregunta,
                        'tipo'                     => $det->tipo,
                        'valor'                    => $det->valor,
                        // siempre devolvemos _ambas_ formas:
                        'respuesta_correcta_id'    => $det->respuesta_correcta_id,
                        'respuesta_correcta_label' => match($det->tipo) {
                            // texto puro
                            'texto','numero'          => $det->respuesta_correcta,
                            // likert
                            'likert'                  => optional($det->opcionLikertCorrecta)->label,
                            // cualquiera de las otras opciones discretas
                            default                   => optional($det->respuestaOpcionCorrecta)->label,
                        },
                        'subpregunta_id'           => $det->tipo === 'likert' ? $det->subpregunta_id : null,
                        'subpregunta_texto'        => $det->tipo === 'likert' 
                                                     ? optional($det->subpregunta)->texto 
                                                     : null,
                    ])
                    ->values(),
            ]);

            return response()->json($result, Response::HTTP_OK);
        }
        catch (\Throwable $e) {
            Log::error('[PONDERACION][COMPLETO] Error: '.$e->getMessage());
            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        $rules = [
        'plan_id'       => 'required|integer|exists:planes_intervencion,id',
        'evaluacion_id' => 'required|integer|exists:evaluaciones,id',
        'detalles'      => 'required|array|min:1',
        'detalles.*.pregunta_id'        => 'required|integer|exists:preguntas,id',
        'detalles.*.tipo'               => 'required|string|in:texto,numero,barra_satisfaccion,si_no,5emojis,opcion_personalizada,si_no_noestoyseguro,likert',
        'detalles.*.valor'              => 'required|numeric|min:0',
        ];
        // mismas condiciones condicionales de respuesta_correcta_id, subpregunta_id, etc.
        $validator = Validator::make($request->all(), $rules);
        // ... your sometimes() para respuesta_correcta_id y subpregunta_id
        if ($validator->fails()) {
        return response()->json($validator->errors(), 422);
        }

        DB::beginTransaction();
        try {
        // 2) Actualizar campos de la cabecera
        $ponderacion = Ponderacion::findOrFail($id);
        $ponderacion->update([
            'plan_id'       => $request->plan_id,
            'evaluacion_id' => $request->evaluacion_id,
        ]);

        // 3) Borrar todos los detalles viejos
        $ponderacion->detalles()->delete();

        // 4) Insertar los nuevos
        foreach ($request->detalles as $det) {
            $detalle = new DetallePonderacion([
            'pregunta_id'           => $det['pregunta_id'],
            'tipo'                  => $det['tipo'],
            'valor'                 => $det['valor'],
            'respuesta_correcta'    => $det['tipo'] === 'texto' ? $det['respuesta_correcta'] : null,
            'respuesta_correcta_id' => in_array($det['tipo'], ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada','likert'])
                                        ? $det['respuesta_correcta_id'] : null,
            'subpregunta_id'        => $det['tipo'] === 'likert' ? $det['subpregunta_id'] : null,
            ]);
            $ponderacion->detalles()->save($detalle);
        }

        DB::commit();
        return response()->json($ponderacion->load('detalles'), 200);
        } catch (\Throwable $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function completoPorId($id)
    {
        $p = Ponderacion::with([
            'evaluacion:id,nombre',
            'detalles.pregunta:id,pregunta',
            'detalles.subpregunta:id,texto',
            'detalles.respuestaOpcionCorrecta:id,label',
            'detalles.opcionLikertCorrecta:id,label',
        ])->findOrFail($id);

        return response()->json([
            'id'                => $p->id,
            'plan_id'           => $p->plan_id,
            'evaluacion_id'     => $p->evaluacion_id,
            'evaluacion_nombre' => $p->evaluacion->nombre,
            'detalles'          => $p->detalles
                ->map(fn($det) => [
                    'id'                       => $det->id,
                    'pregunta_id'              => $det->pregunta_id,
                    'pregunta_texto'           => $det->pregunta->pregunta,
                    'tipo'                     => $det->tipo,
                    'valor'                    => $det->valor,
                    // Para texto guardamos el texto en `respuesta_correcta`
                    'respuesta_correcta'       => $det->tipo === 'texto' ? $det->respuesta_correcta : null,
                    // En todos los demás tipos guardamos el FK en respuesta_correcta_id
                    'respuesta_correcta_id'    => in_array($det->tipo, ['si_no','si_no_noestoyseguro','5emojis','opcion_personalizada','likert'])
                                                  ? $det->respuesta_correcta_id
                                                  : null,
                    // Y por fin la etiqueta legible
                    'respuesta_correcta_label' => match($det->tipo) {
                        'texto','numero'          => $det->respuesta_correcta,
                        'likert'                  => optional($det->opcionLikertCorrecta)->label,
                        default                   => optional($det->respuestaOpcionCorrecta)->label,
                    },
                    'subpregunta_id'           => $det->tipo === 'likert' ? $det->subpregunta_id : null,
                    'subpregunta_texto'        => $det->tipo === 'likert' 
                                                 ? optional($det->subpregunta)->texto 
                                                 : null,
                ])
                ->values(),
        ], Response::HTTP_OK);
    }

    public function existeDetallePorPregunta($preguntaId)
    {
        $tiene = DetallePonderacion::where('pregunta_id', $preguntaId)->exists();
        return response()->json(['tiene' => $tiene]);
    }

        public function destroy($evaluacionId)
    {
        DB::beginTransaction();

        try {
            // 1) Traemos todas las cabeceras para esa evaluación
            $ponderaciones = Ponderacion::where('evaluacion_id', $evaluacionId)->get();

            // 2) Por cada cabecera, borramos sus detalles vía relación
            foreach ($ponderaciones as $ponderacion) {
                $ponderacion->detalles()->delete();
            }

            // 3) Borramos las propias cabeceras
            Ponderacion::where('evaluacion_id', $evaluacionId)->delete();

            DB::commit();

            return response()->json([
                'message' => 'Ponderaciones y detalles eliminados correctamente.'
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'error' => 'Error al limpiar la evaluación: '.$e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroyDetalle($detalleId)
    {
        $detalle = DetallePonderacion::find($detalleId);

        if (! $detalle) {
            return response()->json([
                'message' => 'Detalle no encontrado.'
            ], Response::HTTP_NOT_FOUND);
        }

        $detalle->delete();

        return response()->json([
            'message' => 'Detalle eliminado correctamente.'
        ], Response::HTTP_OK);
    }
}

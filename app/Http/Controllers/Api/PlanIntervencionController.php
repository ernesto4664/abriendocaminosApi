<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanIntervencion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\Evaluacion;
use App\Models\RespuestaOpcion;
use App\Models\RespuestaTipo;
use App\Models\RespuestaSubpregunta;
use App\Models\OpcionLikert;
use App\Models\OpcionBarraSatisfaccion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class PlanIntervencionController extends Controller
{
    public function index()
    {
        try {
            $planes = PlanIntervencion::with(['evaluaciones', 'linea'])->get();
            return response()->json($planes, Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error en PlanIntervencionController@index: ' . $e->getMessage());
            return response()->json(['message' => 'Error al listar planes de intervención'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function indexCompleto()
    {
        Log::info('[indexCompleto] Inicio');
        try {
            $planes = PlanIntervencion::with([
                'evaluaciones.preguntas.tiposDeRespuesta',
                'evaluaciones.preguntas.respuestas.opciones',
                'evaluaciones.preguntas.respuestas.subpreguntas.opciones',
                'evaluaciones.preguntas.respuestas.opcionesBarraSatisfaccion',
                'evaluaciones.preguntas.respuestas.opcionesLikert',
            ])->get();
            Log::info('[indexCompleto] Cargados ' . $planes->count() . ' planes');
            return response()->json($planes, Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('Error en PlanIntervencionController@indexCompleto: ' . $e->getMessage());
            return response()->json(['message' => 'Error al cargar planes completos'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        Log::info('[STORE] Crear PlanIntervencion', ['data' => $request->all()]);
        $request->validate([
            'nombre'                => 'required|string|max:255',
            'descripcion'           => 'nullable|string',
            'linea_id'              => 'required|exists:lineasdeintervenciones,id',
            'evaluaciones'          => 'required|array',
            'evaluaciones.*.nombre' => 'required|string|max:255',
            'evaluaciones.*.preguntas'            => 'required|array',
            'evaluaciones.*.preguntas.*.pregunta' => 'required|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $plan = PlanIntervencion::create($request->only('nombre', 'descripcion', 'linea_id'));
            foreach ($request->evaluaciones as $ev) {
                $evaluacion = $plan->evaluaciones()->create(['nombre' => $ev['nombre']]);
                foreach ($ev['preguntas'] as $pq) {
                    $evaluacion->preguntas()->create(['pregunta' => $pq['pregunta']]);
                }
            }
            DB::commit();
            return response()->json($plan->load('evaluaciones.preguntas', 'linea'), Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error en PlanIntervencionController@store: ' . $e->getMessage());
            return response()->json(['message' => 'Error al crear plan de intervención'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {
            $plan = PlanIntervencion::with(['evaluaciones.preguntas', 'linea'])->findOrFail($id);
            return response()->json($plan, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Plan no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error("Error en PlanIntervencionController@show id={$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error al obtener plan de intervención'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        Log::info("[UPDATE] Plan {$id}", ['data' => $request->all()]);
        $request->validate([
            'nombre'      => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'linea_id'    => 'sometimes|required|exists:lineasdeintervenciones,id',
        ]);

        DB::beginTransaction();
        try {
            $plan = PlanIntervencion::findOrFail($id);
            $plan->update($request->only('nombre', 'descripcion', 'linea_id'));
            DB::commit();
            return response()->json($plan->load('evaluaciones.preguntas', 'linea'), Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Plan no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error en PlanIntervencionController@update id={$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar plan de intervención'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $plan = PlanIntervencion::findOrFail($id);
            $plan->delete();
            return response()->json(['message' => 'Plan eliminado correctamente'], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Plan no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error("Error en PlanIntervencionController@destroy id={$id}: " . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar plan de intervención'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPlanPorTerritorio($territorioId)
    {
        try {
            $planes = PlanIntervencion::where('territorio_id', $territorioId)
                ->with('evaluaciones.preguntas', 'linea')
                ->get();
            return response()->json($planes, Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Error en getPlanPorTerritorio {$territorioId}: {$e->getMessage()}");
            return response()->json(['message' => 'Error al obtener planes por territorio'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getPlanesPorLinea($lineaId)
    {
        try {
            $planes = PlanIntervencion::where('linea_id', $lineaId)
                ->with('evaluaciones.preguntas', 'linea')
                ->get();
            return response()->json($planes, Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error("Error en getPlanesPorLinea {$lineaId}: {$e->getMessage()}");
            return response()->json(['message' => 'Error al obtener planes por línea'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getEvaluacionesConPreguntas(int $planId)
    {
        try {
            
            // 1) Cargar plan con evaluaciones sin ponderaciones + relaciones necesarias
            $plan = PlanIntervencion::with([
                'evaluaciones' => function ($qe) {
                    $qe->doesntHave('ponderaciones')
                        ->with([
                            'preguntas.respuestas.opciones',
                            'preguntas.respuestas.opcionesBarraSatisfaccion',
                            'preguntas.respuestas.subpreguntas.opcionesLikert',
                            'preguntas.tiposDeRespuesta',
                        ]);
                }
            ])->findOrFail($planId);

            // 2) Inyectar opciones falsas según el tipo de pregunta
            foreach ($plan->evaluaciones as $ev) {
                foreach ($ev->preguntas as $preg) {
                    $tipo = optional($preg->tiposDeRespuesta->first())->tipo;

                    // Inyección de respuestas tipo SI/NO
                    if (in_array($tipo, ['si_no', 'si_no_noestoyseguro'], true)) {
                        Log::info("[PlanIntervencion] Inyectando SI/NO en pregunta ID={$preg->id} tipo={$tipo}");

                        $opts = [
                            (object)['id' => 1, 'valor' => 1, 'label' => 'SI'],
                            (object)['id' => 2, 'valor' => 2, 'label' => 'NO'],
                        ];
                        if ($tipo === 'si_no_noestoyseguro') {
                            $opts[] = (object)['id' => 3, 'valor' => null, 'label' => 'No estoy seguro'];
                        }

                        $pseudoRespuesta = (object)[
                            'opciones'                    => $opts,
                            'subpreguntas'                => [],
                            'opciones_barra_satisfaccion' => [],
                        ];

                        $preg->setRelation('respuestas', collect([$pseudoRespuesta]));
                    }

                    // Inyección para tipo texto o número (para que el frontend no lo descarte)
                    if (in_array($tipo, ['texto', 'numero'], true)) {
                        $pseudoRespuesta = (object)[
                            'opciones'                    => [],
                            'subpreguntas'                => [],
                            'opciones_barra_satisfaccion' => [],
                            'respuesta'                   => '', // se puede usar para representar respuesta vacía
                        ];

                        $preg->setRelation('respuestas', collect([$pseudoRespuesta]));
                    }
                }
            }

            // 3) Filtrar solo evaluaciones completas
            $evaluacionesCompletas = $plan->evaluaciones->filter(function ($ev) {
                return $ev->preguntas->every(function ($preg) {
                    $resps = $preg->getRelation('respuestas') instanceof \Illuminate\Support\Collection
                        ? $preg->respuestas
                        : collect($preg->respuestas);

                    if ($resps->isEmpty()) {
                        return false;
                    }

                    $first = $resps->first();
                    if (! $first) {
                        return false;
                    }

                    $opc    = collect($first->opciones ?? []);
                    $barra  = collect($first->opciones_barra_satisfaccion ?? []);
                    $likert = collect($first->subpreguntas ?? [])->pluck('opciones_likert')->flatten();
                    $tipo   = optional($preg->tiposDeRespuesta->first())->tipo;

                    // Aceptar tipo texto o número con solo existencia de respuesta
                    if (in_array($tipo, ['texto', 'numero'])) {
                        return true;
                    }

                    return $opc->isNotEmpty() || $barra->isNotEmpty() || $likert->isNotEmpty();
                });
            })->values();

            Log::info("[PlanIntervencion][getEvaluacionesConPreguntas] Evaluaciones válidas encontradas: {$evaluacionesCompletas->count()}");

            // 4) Retornar
            return response()->json([
                'evaluaciones' => $evaluacionesCompletas
            ], Response::HTTP_OK);

        } catch (ModelNotFoundException $e) {
            Log::warning("[PlanIntervencion][getEvaluacionesConPreguntas] Plan {$planId} no encontrado.");
            return response()->json(
                ['message' => 'Plan de intervención no encontrado.'],
                Response::HTTP_NOT_FOUND
            );
        } catch (\Throwable $e) {
            Log::error("[PlanIntervencion][getEvaluacionesConPreguntas] Error inesperado: " . $e->getMessage());
            return response()->json(
                ['message' => 'Error al obtener evaluaciones.'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanIntervencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PlanIntervencionController extends Controller
{
    /**
     * Listar todos los planes de intervención
     * → Devuelve un array puro para el front
     */
    public function index()
    {
        try {
            $planes = PlanIntervencion::with(['evaluaciones', 'linea'])->get();
            return response()->json($planes, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error en PlanIntervencionController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar planes de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Listar planes con todos sus detalles (evaluaciones, preguntas, respuestas, etc.)
     * → Devuelve un array puro
     */
    public function indexCompleto()
    {
        Log::info('[indexCompleto] Inicio');
        try {
            $planes = PlanIntervencion::with([
                'evaluaciones.preguntas.tiposDeRespuesta',
                'evaluaciones.preguntas.respuestas.opciones',
                'evaluaciones.preguntas.respuestas.subpreguntas.opcionesLikert',
                'evaluaciones.preguntas.respuestas.opcionesBarraSatisfaccion',
                'evaluaciones.preguntas.respuestas.opcionesLikert',
            ])->get();
            Log::info('[indexCompleto] Cargados ' . $planes->count() . ' planes');
            return response()->json($planes, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error en PlanIntervencionController@indexCompleto: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al cargar planes completos'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear un nuevo plan de intervención con sus evaluaciones y preguntas
     * → Devuelve el objeto PlanIntervencion creado
     */
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
            return response()->json([
                'message' => 'Error al crear plan de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar un plan de intervención por ID
     * → Devuelve el objeto PlanIntervencion
     */
    public function show($id)
    {
        try {
            $plan = PlanIntervencion::with(['evaluaciones.preguntas', 'linea'])->findOrFail($id);
            return response()->json($plan, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Plan no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en PlanIntervencionController@show id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener plan de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar un plan de intervención
     * → Devuelve el objeto actualizado
     */
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
            return response()->json([
                'message' => 'Plan no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error en PlanIntervencionController@update id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar plan de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un plan de intervención
     * → Devuelve mensaje de confirmación
     */
    public function destroy($id)
    {
        try {
            $plan = PlanIntervencion::findOrFail($id);
            $plan->delete();
            return response()->json([
                'message' => 'Plan eliminado correctamente'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Plan no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en PlanIntervencionController@destroy id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al eliminar plan de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

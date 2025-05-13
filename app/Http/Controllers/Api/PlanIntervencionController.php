<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PlanIntervencion;
use App\Models\MDSFApiResponse;
use App\Models\Evaluacion;
use App\Models\Pregunta;
use App\Models\Respuesta;
use App\Models\RespuestaOpcion;
use App\Models\RespuestaSubpregunta;
use App\Models\RespuestaTipo;
use App\Models\LineasDeIntervencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlanIntervencionController extends Controller
{
    public function index()
    {
        $resp = new MDSFApiResponse();
        try {
            $resp->data = PlanIntervencion::with(['evaluaciones', 'linea'])->get();
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('Error en PlanIntervencionController@index: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al listar planes de intervención';
        }
        return $resp->json();
    }

    public function indexCompleto()
    {
        $resp = new MDSFApiResponse();
        Log::info('[indexCompleto] Inicio');
        try {
            $planes = PlanIntervencion::with([
                'evaluaciones.preguntas.tiposDeRespuesta',
                'evaluaciones.preguntas.respuestas.opciones',
                'evaluaciones.preguntas.respuestas.subpreguntas.opcionesLikert',
                'evaluaciones.preguntas.respuestas.opcionesBarraSatisfaccion',
                'evaluaciones.preguntas.respuestas.opcionesLikert',
            ])->get();
            Log::info('[indexCompleto] Cargados '.$planes->count().' planes');
            $resp->data = $planes;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('Error en PlanIntervencionController@indexCompleto: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al cargar planes completos';
        }
        return $resp->json();
    }

    public function store(Request $request)
    {
        $resp = new MDSFApiResponse();
        Log::info('[STORE] Crear PlanIntervencion', ['data'=>$request->all()]);
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
            $plan = PlanIntervencion::create($request->only('nombre','descripcion','linea_id'));

            foreach ($request->evaluaciones as $ev) {
                $evaluacion = Evaluacion::create([
                    'plan_id'=> $plan->id, 'nombre'=> $ev['nombre']
                ]);
                foreach ($ev['preguntas'] as $pq) {
                    Pregunta::create([
                        'evaluacion_id'=> $evaluacion->id,
                        'pregunta'     => $pq['pregunta']
                    ]);
                }
            }
            DB::commit();

            $resp->data = $plan->load('evaluaciones.preguntas','linea');
            $resp->code = 201;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en PlanIntervencionController@store: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al crear plan de intervención';
        }
        return $resp->json();
    }

    public function show($id)
    {
        $resp = new MDSFApiResponse();
        try {
            $plan = PlanIntervencion::with(['evaluaciones.preguntas','linea'])->findOrFail($id);
            $resp->data = $plan;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error("Error en PlanIntervencionController@show id={$id}: ".$e->getMessage());
            $resp->code    = 404;
            $resp->message = 'Plan no encontrado';
        }
        return $resp->json();
    }

    public function update(Request $request, $id)
    {
        $resp = new MDSFApiResponse();
        Log::info("[UPDATE] Plan {$id}", ['data'=>$request->all()]);
        $request->validate([
            'nombre'      => 'sometimes|required|string|max:255',
            'descripcion' => 'nullable|string',
            'linea_id'    => 'sometimes|required|exists:lineasdeintervenciones,id',
            // demás validaciones si necesitas
        ]);

        DB::beginTransaction();
        try {
            $plan = PlanIntervencion::findOrFail($id);
            $plan->update($request->only('nombre','descripcion','linea_id'));
            // aquí podrías actualizar evaluaciones/preguntas como antes…

            DB::commit();
            $resp->data = $plan->load('evaluaciones.preguntas','linea');
            $resp->code = 200;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error en PlanIntervencionController@update id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al actualizar plan de intervención';
        }
        return $resp->json();
    }

    public function destroy($id)
    {
        $resp = new MDSFApiResponse();
        try {
            PlanIntervencion::destroy($id);
            $resp->message = 'Plan eliminado correctamente';
            $resp->code    = 200;
        } catch (\Exception $e) {
            Log::error("Error en PlanIntervencionController@destroy id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al eliminar plan de intervención';
        }
        return $resp->json();
    }

    // Métodos adicionales también deberían usar el mismo patrón...
}

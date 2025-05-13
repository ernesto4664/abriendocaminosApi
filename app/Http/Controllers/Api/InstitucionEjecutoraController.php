<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MDSFApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\InstitucionEjecutora;
use App\Models\LineasDeIntervencion;
use App\Models\Evaluacion;
use App\Models\Pregunta;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use Illuminate\Support\Facades\Log;

class InstitucionEjecutoraController extends Controller
{
    public function index(Request $request)
    {
        $respuesta = new MDSFApiResponse();

        try {
            $query = InstitucionEjecutora::with([
                'planDeIntervencion',
                'territorio',
                'planDeIntervencion.evaluaciones.preguntas'
            ]);

            if ($request->has('region_id')) {
                $regionId = (int)$request->region_id;
                $query->whereHas('territorio', fn($q) =>
                    $q->whereJsonContains('region_id', $regionId)
                );
            }

            $instituciones = $query->get()->map(function ($inst) {
                if ($inst->territorio) {
                    $inst->territorio->regiones   = $inst->territorio->regiones;
                    $inst->territorio->provincias = $inst->territorio->provincias;
                    $inst->territorio->comunas    = $inst->territorio->comunas;
                }
                if ($inst->planDeIntervencion) {
                    $inst->planDeIntervencion->evaluaciones =
                        $inst->planDeIntervencion->evaluaciones->map(fn($ev) => tap($ev, fn($e) => $e->preguntas = $e->preguntas));
                }
                return $inst;
            });

            $respuesta->data = $instituciones;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            Log::error('Error al listar instituciones: ' . $e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error al listar instituciones ejecutoras';
        }

        return $respuesta->json();
    }

    public function store(Request $request)
    {
        $respuesta = new MDSFApiResponse();

        $request->validate([
            'nombre_fantasia'               => 'required|string|max:255',
            'nombre_legal'                  => 'required|string|max:255',
            'rut'                           => 'required|string|max:20',
            'telefono'                      => 'required|string|max:15',
            'email'                         => 'required|email|max:255',
            'territorio_id'                 => 'required|exists:territorios,id',
            'periodo_registro_desde'        => 'required|date',
            'periodo_registro_hasta'        => 'required|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde'     => 'required|date',
            'periodo_seguimiento_hasta'     => 'required|date|after_or_equal:periodo_seguimiento_desde',
        ]);

        DB::beginTransaction();
        try {
            $institucion = InstitucionEjecutora::create($request->all());
            DB::commit();

            $respuesta->data = $institucion;
            $respuesta->code = 201;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear institución ejecutora: ' . $e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error interno al crear la institución ejecutora';
        }

        return $respuesta->json();
    }

    public function show($id)
    {
        $respuesta = new MDSFApiResponse();

        try {
            $institucion = InstitucionEjecutora::with(['planDeIntervencion', 'territorio'])
                ->findOrFail($id);

            if ($institucion->territorio) {
                $institucion->territorio->regiones   = $institucion->territorio->regiones;
                $institucion->territorio->provincias = $institucion->territorio->provincias;
                $institucion->territorio->comunas    = $institucion->territorio->comunas;
            }

            $respuesta->data = $institucion;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            Log::error("Error al obtener institución {$id}: " . $e->getMessage());
            $respuesta->code    = 404;
            $respuesta->message = 'Institución ejecutora no encontrada';
        }

        return $respuesta->json();
    }

    public function update(Request $request, $id)
    {
        $respuesta = new MDSFApiResponse();

        $request->validate([
            'nombre_fantasia'               => 'sometimes|required|string|max:255',
            'nombre_legal'                  => 'sometimes|required|string|max:255',
            'rut'                           => 'sometimes|required|string|max:20',
            'telefono'                      => 'sometimes|required|string|max:15',
            'email'                         => 'sometimes|required|email|max:255',
            'territorio_id'                 => 'sometimes|required|exists:territorios,id',
            'periodo_registro_desde'        => 'sometimes|required|date',
            'periodo_registro_hasta'        => 'sometimes|required|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde'     => 'sometimes|required|date',
            'periodo_seguimiento_hasta'     => 'sometimes|required|date|after_or_equal:periodo_seguimiento_desde',
        ]);

        DB::beginTransaction();
        try {
            $institucion = InstitucionEjecutora::findOrFail($id);
            $institucion->update($request->all());
            DB::commit();

            $respuesta->data = $institucion;
            $respuesta->code = 200;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al actualizar institución {$id}: " . $e->getMessage());
            $respuesta->code    = isset($institucion) ? 500 : 404;
            $respuesta->message = isset($institucion)
                ? 'Error al actualizar la institución ejecutora'
                : 'Institución ejecutora no encontrada';
        }

        return $respuesta->json();
    }

    public function destroy($id)
    {
        $respuesta = new MDSFApiResponse();

        try {
            $institucion = InstitucionEjecutora::findOrFail($id);
            $institucion->delete();

            $respuesta->message = 'Institución ejecutora eliminada correctamente';
            $respuesta->code    = 200;
        } catch (\Exception $e) {
            Log::error("Error al eliminar institución {$id}: " . $e->getMessage());
            $respuesta->code    = 404;
            $respuesta->message = 'Institución ejecutora no encontrada o no pudo eliminarse';
        }

        return $respuesta->json();
    }
}

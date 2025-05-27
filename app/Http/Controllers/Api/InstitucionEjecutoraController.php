<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstitucionEjecutora;
use App\Models\LineasDeIntervencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InstitucionEjecutoraController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = InstitucionEjecutora::with([
                'planDeIntervencion',
                'territorio.linea.planDeIntervencion',
                'planDeIntervencion.evaluaciones.preguntas'
            ]);

            if ($request->filled('region_id')) {
                $regionId = (int) $request->region_id;
                $query->whereHas('territorio', fn($q) =>
                    $q->whereJsonContains('region_id', $regionId)
                );
            }

            $instituciones = $query->get();

            $institucionesArray = $instituciones->map(function ($inst) {
                // Si no tiene plan asignado directamente, lo heredamos desde la línea
                if (!$inst->planDeIntervencion && $inst->territorio && $inst->territorio->linea && $inst->territorio->linea->planDeIntervencion) {
                    $inst->planDeIntervencion = $inst->territorio->linea->planDeIntervencion;
                }

                // Unificamos para que también venga como 'plan_de_intervencion'
                if (!$inst->plan_de_intervencion && $inst->planDeIntervencion) {
                    $inst->plan_de_intervencion = $inst->planDeIntervencion;
                }

                // Forzamos carga de regiones, provincias y comunas
                if ($inst->territorio) {
                    $inst->territorio->regiones;
                    $inst->territorio->provincias;
                    $inst->territorio->comunas;
                }

                return $inst->toArray();
            });

            return response()->json($institucionesArray, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error al listar instituciones ejecutoras: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar instituciones ejecutoras'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_fantasia'            => 'required|string|max:255',
            'nombre_legal'               => 'required|string|max:255',
            'rut'                        => 'required|string|max:20',
            'telefono'                   => 'required|string|max:15',
            'email'                      => 'required|email|max:255',
            'territorio_id'              => 'required|exists:territorios,id',
            'plazas'                     => 'nullable|integer|min:0',
            'planesdeintervencion_id'   => 'nullable|exists:planes_intervencion,id',
            'periodo_registro_desde'     => 'required|date',
            'periodo_registro_hasta'     => 'required|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde'  => 'required|date',
            'periodo_seguimiento_hasta'  => 'required|date|after_or_equal:periodo_seguimiento_desde',
        ]);

        DB::beginTransaction();
        try {
            $institucion = InstitucionEjecutora::create($request->only([
                'nombre_fantasia',
                'nombre_legal',
                'rut',
                'telefono',
                'email',
                'territorio_id',
                'plazas',
                'planesdeintervencion_id',
                'periodo_registro_desde',
                'periodo_registro_hasta',
                'periodo_seguimiento_desde',
                'periodo_seguimiento_hasta',
            ]));
            DB::commit();

            return response()->json($institucion, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear institución ejecutora: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error interno al crear la institución ejecutora'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show($id)
    {
        try {
                $inst = InstitucionEjecutora::with([
                    'planDeIntervencion',
                    'territorio',
                    'territorio.linea.planDeIntervencion',
                ])->findOrFail($id);

                // Si la institución no tiene plan directo, se lo heredamos desde la línea del territorio
                if (!$inst->planDeIntervencion && $inst->territorio && $inst->territorio->linea && $inst->territorio->linea->planDeIntervencion) {
                    $inst->planDeIntervencion = $inst->territorio->linea->planDeIntervencion;
                }
                // ✅ Forzar carga de atributos para que se evalúen y se incluyan en el array final
                if ($inst->territorio) {
                    $inst->territorio->regiones;
                    $inst->territorio->provincias;
                    $inst->territorio->comunas;
                }

            return response()->json($inst->toArray(), Response::HTTP_OK); // 👈 Esto incluye todo

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institución ejecutora no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al obtener institución ejecutora {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener la institución ejecutora'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_fantasia'            => 'sometimes|string|max:255',
            'nombre_legal'               => 'sometimes|string|max:255',
            'rut'                        => 'sometimes|string|max:20',
            'telefono'                   => 'sometimes|string|max:15',
            'email'                      => 'sometimes|email|max:255',
            'territorio_id'              => 'sometimes|exists:territorios,id',
            'plazas'                     => 'nullable|integer|min:0',
            'planesdeintervencion_id'   => 'nullable|exists:planes_intervencion,id',
            'periodo_registro_desde'     => 'sometimes|date',
            'periodo_registro_hasta'     => 'sometimes|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde'  => 'sometimes|date',
            'periodo_seguimiento_hasta'  => 'sometimes|date|after_or_equal:periodo_seguimiento_desde',
        ]);

        DB::beginTransaction();
        try {
            $inst = InstitucionEjecutora::findOrFail($id);
            $inst->update($request->only([
                'nombre_fantasia',
                'nombre_legal',
                'rut',
                'telefono',
                'email',
                'territorio_id',
                'plazas',
                'planesdeintervencion_id',
                'periodo_registro_desde',
                'periodo_registro_hasta',
                'periodo_seguimiento_desde',
                'periodo_seguimiento_hasta',
            ]));
            DB::commit();

            return response()->json($inst, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institución ejecutora no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al actualizar institución ejecutora {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar la institución ejecutora'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy($id)
    {
        try {
            $inst = InstitucionEjecutora::findOrFail($id);
            $inst->delete();

            return response()->json([
                'message' => 'Institución ejecutora eliminada correctamente'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Institución ejecutora no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al eliminar institución ejecutora {$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al eliminar la institución ejecutora'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

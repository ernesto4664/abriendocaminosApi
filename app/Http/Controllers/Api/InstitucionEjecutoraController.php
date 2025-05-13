<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstitucionEjecutora;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class InstitucionEjecutoraController extends Controller
{
    /**
     * Listar instituciones ejecutoras, opcionalmente filtradas por región.
     */
    public function index(Request $request)
    {
        try {
            $query = InstitucionEjecutora::with([
                'planDeIntervencion',
                'territorio',
                'planDeIntervencion.evaluaciones.preguntas'
            ]);

            if ($request->filled('region_id')) {
                $regionId = (int) $request->region_id;
                $query->whereHas('territorio', fn($q) =>
                    $q->whereJsonContains('region_id', $regionId)
                );
            }

            $instituciones = $query->get()->map(function ($inst) {
                // Adjuntar arrays de territorios
                if ($inst->territorio) {
                    $inst->territorio->regiones   = $inst->territorio->regiones;
                    $inst->territorio->provincias = $inst->territorio->provincias;
                    $inst->territorio->comunas    = $inst->territorio->comunas;
                }
                // Asegurar que preguntas queden cargadas
                if ($inst->planDeIntervencion) {
                    $inst->planDeIntervencion->evaluaciones =
                        $inst->planDeIntervencion->evaluaciones->map(
                            fn($ev) => tap($ev, fn($e) => $e->preguntas = $e->preguntas)
                        );
                }
                return $inst;
            });

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $instituciones,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error al listar instituciones ejecutoras: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al listar instituciones ejecutoras',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear una institución ejecutora.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_fantasia'            => 'required|string|max:255',
            'nombre_legal'               => 'required|string|max:255',
            'rut'                        => 'required|string|max:20',
            'telefono'                   => 'required|string|max:15',
            'email'                      => 'required|email|max:255',
            'territorio_id'              => 'required|exists:territorios,id',
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
                'periodo_registro_desde',
                'periodo_registro_hasta',
                'periodo_seguimiento_desde',
                'periodo_seguimiento_hasta',
            ]));
            DB::commit();

            return response()->json([
                'code'    => Response::HTTP_CREATED,
                'data'    => $institucion,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear institución ejecutora: ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error interno al crear la institución ejecutora',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar datos de una institución ejecutora.
     */
    public function show($id)
    {
        try {
            $inst = InstitucionEjecutora::with([
                'planDeIntervencion',
                'territorio'
            ])->findOrFail($id);

            if ($inst->territorio) {
                $inst->territorio->regiones   = $inst->territorio->regiones;
                $inst->territorio->provincias = $inst->territorio->provincias;
                $inst->territorio->comunas    = $inst->territorio->comunas;
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $inst,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Institución ejecutora no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al obtener institución ejecutora {$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener la institución ejecutora',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar una institución ejecutora.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre_fantasia'            => 'sometimes|required|string|max:255',
            'nombre_legal'               => 'sometimes|required|string|max:255',
            'rut'                        => 'sometimes|required|string|max:20',
            'telefono'                   => 'sometimes|required|string|max:15',
            'email'                      => 'sometimes|required|email|max:255',
            'territorio_id'              => 'sometimes|required|exists:territorios,id',
            'periodo_registro_desde'     => 'sometimes|required|date',
            'periodo_registro_hasta'     => 'sometimes|required|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde'  => 'sometimes|required|date',
            'periodo_seguimiento_hasta'  => 'sometimes|required|date|after_or_equal:periodo_seguimiento_desde',
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
                'periodo_registro_desde',
                'periodo_registro_hasta',
                'periodo_seguimiento_desde',
                'periodo_seguimiento_hasta',
            ]));
            DB::commit();

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $inst,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Institución ejecutora no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error("Error al actualizar institución ejecutora {$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al actualizar la institución ejecutora',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar una institución ejecutora.
     */
    public function destroy($id)
    {
        try {
            $inst = InstitucionEjecutora::findOrFail($id);
            $inst->delete();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Institución ejecutora eliminada correctamente',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Institución ejecutora no encontrada',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error al eliminar institución ejecutora {$id}: " . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al eliminar la institución ejecutora',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

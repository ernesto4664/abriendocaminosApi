<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Territorio;
use App\Models\LineasDeIntervencion;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TerritorioController extends Controller
{
    /**
     * Listar todos los territorios con sus relaciones.
     */
    public function index()
    {
        try {
            $territorios = Territorio::with('linea')->get()->map(function ($t) {
                return [
                    'id'           => $t->id,
                    'nombre'       => $t->nombre_territorio,
                    'linea'        => $t->linea?->nombre ?? 'Sin asignar',
                    'regiones'     => $t->regiones,
                    'provincias'   => $t->provincias,
                    'comunas'      => $t->comunas,
                    'plazas'       => $t->plazas,
                    'cuotas'       => [
                        $t->cuota_1,
                        $t->cuota_2,
                        'total' => $t->total,
                    ],
                ];
            });

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $territorios,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Territorio][index] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al listar territorios',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar un territorio por ID.
     */
    public function show($id)
    {
        try {
            $t = Territorio::with('linea')->findOrFail($id);

            $data = [
                'id'           => $t->id,
                'nombre'       => $t->nombre_territorio,
                'linea'        => $t->linea?->nombre ?? 'Sin asignar',
                'regiones'     => $t->regiones,
                'provincias'   => $t->provincias,
                'comunas'      => $t->comunas,
                'plazas'       => $t->plazas,
                'cuotas'       => [
                    $t->cuota_1,
                    $t->cuota_2,
                    'total' => $t->total,
                ],
            ];

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $data,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Territorio no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[Territorio][show] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener territorio',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear un nuevo territorio.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_territorio' => 'required|string|max:255',
            'cod_territorio'    => 'required|integer',
            'comuna_id'         => 'required|array',
            'provincia_id'      => 'required|array',
            'region_id'         => 'required|array',
            'plazas'            => 'nullable|integer',
            'linea_id'          => 'required|integer|exists:lineasdeintervenciones,id',
            'cuota_1'           => 'nullable|numeric',
            'cuota_2'           => 'nullable|numeric',
        ]);

        try {
            $t = Territorio::create(array_merge(
                $request->only([
                    'nombre_territorio', 'cod_territorio',
                    'comuna_id', 'provincia_id', 'region_id',
                    'plazas', 'linea_id', 'cuota_1', 'cuota_2'
                ]),
                ['total' => ($request->cuota_1 ?? 0) + ($request->cuota_2 ?? 0)]
            ));

            return response()->json([
                'code' => Response::HTTP_CREATED,
                'data' => $t,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('[Territorio][store] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al crear territorio',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar un territorio.
     */
    public function update(Request $request, $id)
    {
        try {
            $t = Territorio::findOrFail($id);

            $request->validate([
                'nombre_territorio' => 'sometimes|string|max:255',
                'cod_territorio'    => 'sometimes|integer',
                'comuna_id'         => 'sometimes|array',
                'provincia_id'      => 'sometimes|array',
                'region_id'         => 'sometimes|array',
                'plazas'            => 'nullable|integer',
                'linea_id'          => 'sometimes|integer|exists:lineasdeintervenciones,id',
                'cuota_1'           => 'nullable|numeric',
                'cuota_2'           => 'nullable|numeric',
            ]);

            $data = $request->only([
                'nombre_territorio','cod_territorio',
                'comuna_id','provincia_id','region_id',
                'plazas','linea_id','cuota_1','cuota_2'
            ]);
            $data['total'] = ($data['cuota_1'] ?? $t->cuota_1 ?? 0) +
                              ($data['cuota_2'] ?? $t->cuota_2 ?? 0);
            $t->update($data);

            $t->linea = LineasDeIntervencion::find($t->linea_id)?->nombre;

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $t,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Territorio no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[Territorio][update] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al actualizar territorio',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un territorio.
     */
    public function destroy($id)
    {
        try {
            $t = Territorio::findOrFail($id);
            $t->delete();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Territorio eliminado correctamente',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Territorio no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[Territorio][destroy] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al eliminar territorio',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

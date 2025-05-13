<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class UbicacionController extends Controller
{
    /**
     * Obtener todas las regiones
     */
    public function getRegiones()
    {
        try {
            $regiones = Region::all();

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $regiones,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Ubicacion][getRegiones] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener regiones',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener provincias según una o varias regiones
     */
    public function getProvincias(Request $request)
    {
        $regionIds = $request->query('region_ids');
        if (!$regionIds) {
            return response()->json([
                'code'    => Response::HTTP_BAD_REQUEST,
                'message' => 'Se requieren IDs de regiones',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ids = is_string($regionIds) ? explode(',', $regionIds) : (array)$regionIds;
            $provincias = Provincia::whereIn('region_id', $ids)
                ->with('comunas')
                ->get();

            if ($provincias->isEmpty()) {
                return response()->json([
                    'code'    => Response::HTTP_NOT_FOUND,
                    'message' => 'No se encontraron provincias',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $provincias,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Ubicacion][getProvincias] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener provincias',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Obtener comunas según una o varias provincias
     */
    public function getComunas(Request $request)
    {
        $provinciaIds = $request->query('provincia_ids');
        if (!$provinciaIds) {
            return response()->json([
                'code'    => Response::HTTP_BAD_REQUEST,
                'message' => 'Se requieren IDs de provincias',
            ], Response::HTTP_BAD_REQUEST);
        }

        try {
            $ids = is_string($provinciaIds) ? explode(',', $provinciaIds) : (array)$provinciaIds;
            $comunas = Comuna::whereIn('provincia_id', $ids)->get();

            if ($comunas->isEmpty()) {
                return response()->json([
                    'code'    => Response::HTTP_NOT_FOUND,
                    'message' => 'No se encontraron comunas',
                ], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $comunas,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Ubicacion][getComunas] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener comunas',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

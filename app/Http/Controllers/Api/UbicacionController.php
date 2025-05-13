<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use App\Models\MDSFApiResponse;
use Illuminate\Support\Facades\Log;

class UbicacionController extends Controller
{
    /**
     * Obtener todas las regiones.
     */
    public function getRegiones()
    {
        $resp = new MDSFApiResponse();

        try {
            $resp->data = Region::all();
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('[Ubicacion][getRegiones] ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener regiones';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /**
     * Obtener provincias según una o varias regiones.
     */
    public function getProvincias(Request $request)
    {
        $resp = new MDSFApiResponse();

        $regionIds = $request->query('region_ids');
        if (!$regionIds) {
            $resp->code    = 400;
            $resp->message = 'No se han enviado regiones';
            return $resp->json();
        }

        try {
            $ids = is_string($regionIds) ? explode(',', $regionIds) : (array)$regionIds;
            $provincias = Provincia::whereIn('region_id', $ids)->with('comunas')->get();

            if ($provincias->isEmpty()) {
                $resp->code    = 404;
                $resp->message = 'No se encontraron provincias';
            } else {
                $resp->data = $provincias;
                $resp->code = 200;
            }
        } catch (\Exception $e) {
            Log::error('[Ubicacion][getProvincias] ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener provincias';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /**
     * Obtener comunas según una o varias provincias.
     */
    public function getComunas(Request $request)
    {
        $resp = new MDSFApiResponse();

        $provinciaIds = $request->query('provincia_ids');
        if (!$provinciaIds) {
            $resp->code    = 400;
            $resp->message = 'No se han proporcionado IDs de provincia';
            return $resp->json();
        }

        try {
            $ids = is_string($provinciaIds) ? explode(',', $provinciaIds) : (array)$provinciaIds;
            $comunas = Comuna::whereIn('provincia_id', $ids)->get();

            if ($comunas->isEmpty()) {
                $resp->code    = 404;
                $resp->message = 'No se encontraron comunas';
            } else {
                $resp->data = $comunas;
                $resp->code = 200;
            }
        } catch (\Exception $e) {
            Log::error('[Ubicacion][getComunas] ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener comunas';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }
}

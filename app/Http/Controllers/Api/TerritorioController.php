<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Territorio;
use App\Models\LineasDeIntervencion;
use App\Models\MDSFApiResponse;
use Illuminate\Support\Facades\Log;

class TerritorioController extends Controller
{
    /**
     * Listar todos los territorios con sus relaciones.
     */
    public function index()
    {
        $resp = new MDSFApiResponse();

        try {
            $territorios = Territorio::with('linea')->get()->map(function($t) {
                $t->regiones   = $t->regiones;
                $t->provincias = $t->provincias;
                $t->comunas    = $t->comunas;
                $t->linea      = $t->linea ? $t->linea->nombre : 'Sin asignar';
                return $t;
            });

            $resp->data    = $territorios;
            $resp->code    = 200;
        } catch (\Exception $e) {
            Log::error('[Territorio][index] '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al listar territorios';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /**
     * Mostrar un territorio por ID.
     */
    public function show($id)
    {
        $resp = new MDSFApiResponse();

        try {
            $t = Territorio::with('linea')->find($id);
            if (!$t) {
                $resp->code    = 404;
                $resp->message = 'Territorio no encontrado';
                return $resp->json();
            }

            $t->regiones   = $t->regiones;
            $t->provincias = $t->provincias;
            $t->comunas    = $t->comunas;
            $t->linea      = $t->linea ? $t->linea->nombre : 'Sin asignar';

            $resp->data = $t;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('[Territorio][show] '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener territorio';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /**
     * Crear un nuevo territorio.
     */
    public function store(Request $request)
    {
        $resp = new MDSFApiResponse();

        $request->validate([
            'nombre_territorio' => 'required|string|max:255',
            'cod_territorio'    => 'required|integer',
            'comuna_id'         => 'required|array',
            'provincia_id'      => 'required|array',
            'region_id'         => 'required|array',
            'plazas'            => 'nullable|integer',
            'linea_id'          => 'required|integer',
            'cuota_1'           => 'nullable|numeric',
            'cuota_2'           => 'nullable|numeric',
        ]);

        try {
            $t = Territorio::create([
                'nombre_territorio' => $request->nombre_territorio,
                'cod_territorio'    => $request->cod_territorio,
                'comuna_id'         => $request->comuna_id,
                'provincia_id'      => $request->provincia_id,
                'region_id'         => $request->region_id,
                'plazas'            => $request->plazas,
                'linea_id'          => $request->linea_id,
                'cuota_1'           => $request->cuota_1,
                'cuota_2'           => $request->cuota_2,
                'total'             => ($request->cuota_1 ?? 0) + ($request->cuota_2 ?? 0),
            ]);

            $resp->data = $t;
            $resp->code = 201;
        } catch (\Exception $e) {
            Log::error('[Territorio][store] '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al crear territorio';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /**
     * Actualizar un territorio.
     */
    public function update(Request $request, $id)
    {
        $resp = new MDSFApiResponse();

        try {
            $t = Territorio::find($id);
            if (!$t) {
                $resp->code    = 404;
                $resp->message = 'Territorio no encontrado';
                return $resp->json();
            }

            $request->validate([
                'nombre_territorio' => 'sometimes|string|max:255',
                'cod_territorio'    => 'sometimes|integer',
                'comuna_id'         => 'sometimes|array',
                'provincia_id'      => 'sometimes|array',
                'region_id'         => 'sometimes|array',
                'plazas'            => 'nullable|integer',
                'linea_id'          => 'sometimes|integer',
                'cuota_1'           => 'nullable|numeric',
                'cuota_2'           => 'nullable|numeric',
            ]);

            $t->update([
                'nombre_territorio' => $request->nombre_territorio ?? $t->nombre_territorio,
                'cod_territorio'    => $request->cod_territorio ?? $t->cod_territorio,
                'comuna_id'         => $request->has('comuna_id') ? array_map('intval',$request->comuna_id) : $t->comuna_id,
                'provincia_id'      => $request->has('provincia_id') ? array_map('intval',$request->provincia_id) : $t->provincia_id,
                'region_id'         => $request->has('region_id') ? array_map('intval',$request->region_id) : $t->region_id,
                'plazas'            => $request->plazas ?? $t->plazas,
                'linea_id'          => $request->linea_id ?? $t->linea_id,
                'cuota_1'           => $request->cuota_1 ?? $t->cuota_1,
                'cuota_2'           => $request->cuota_2 ?? $t->cuota_2,
                'total'             => ($request->cuota_1 ?? $t->cuota_1 ?? 0) + ($request->cuota_2 ?? $t->cuota_2 ?? 0),
            ]);

            $t->linea = $t->linea_id
                ? LineasDeIntervencion::where('id',$t->linea_id)->value('nombre')
                : null;

            $resp->data = $t;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('[Territorio][update] '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al actualizar territorio';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    /**
     * Eliminar un territorio.
     */
    public function destroy($id)
    {
        $resp = new MDSFApiResponse();

        try {
            $t = Territorio::find($id);
            if (!$t) {
                $resp->code    = 404;
                $resp->message = 'Territorio no encontrado';
                return $resp->json();
            }

            $t->delete();
            $resp->code    = 200;
            $resp->message = 'Territorio eliminado correctamente';
        } catch (\Exception $e) {
            Log::error('[Territorio][destroy] '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al eliminar territorio';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }
}

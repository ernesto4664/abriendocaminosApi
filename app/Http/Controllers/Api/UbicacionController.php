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
     * → Devuelve un array puro de territorios con sus datos normalizados
     */
    public function index()
    {
        try {
            $territorios = Territorio::with('linea')->get()->map(fn($t) => [
                'id'         => $t->id,
                'nombre'     => $t->nombre_territorio,
                'linea'      => $t->linea?->nombre ?? 'Sin asignar',
                'regiones'   => $t->regiones,
                'provincias' => $t->provincias,
                'comunas'    => $t->comunas,
                'plazas'     => $t->plazas,
                'cuotas'     => [
                    'cuota_1' => $t->cuota_1,
                    'cuota_2' => $t->cuota_2,
                    'total'   => $t->total,
                ],
            ]);

            return response()->json($territorios, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Territorio][index] ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar territorios'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar un territorio por ID.
     * → Devuelve el objeto territorio normalizado
     */
    public function show($id)
    {
        try {
            $t = Territorio::with('linea')->findOrFail($id);
            $data = [
                'id'         => $t->id,
                'nombre'     => $t->nombre_territorio,
                'linea'      => $t->linea?->nombre ?? 'Sin asignar',
                'regiones'   => $t->regiones,
                'provincias' => $t->provincias,
                'comunas'    => $t->comunas,
                'plazas'     => $t->plazas,
                'cuotas'     => [
                    'cuota_1' => $t->cuota_1,
                    'cuota_2' => $t->cuota_2,
                    'total'   => $t->total,
                ],
            ];

            return response()->json($data, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Territorio no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[Territorio][show] ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener territorio'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear un nuevo territorio.
     * → Devuelve el objeto creado
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
                    'nombre_territorio','cod_territorio',
                    'comuna_id','provincia_id','region_id',
                    'plazas','linea_id','cuota_1','cuota_2'
                ]),
                ['total' => ($request->cuota_1 ?? 0)+($request->cuota_2 ?? 0)]
            ));
            return response()->json($t, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('[Territorio][store] ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear territorio'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar un territorio.
     * → Devuelve el objeto actualizado
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
            $data['total'] = ($data['cuota_1'] ?? $t->cuota_1 ?? 0)
                           + ($data['cuota_2'] ?? $t->cuota_2 ?? 0);
            $t->update($data);
            return response()->json($t, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Territorio no encontrado'], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[Territorio][update] ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar territorio'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar un territorio.
     * → Devuelve mensaje de confirmación
     */
    public function destroy($id)
    {
        try {
            Territorio::findOrFail($id)->delete();
            return response()->json(['message' => 'Territorio eliminado correctamente'], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Territorio no encontrado'], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[Territorio][destroy] ' . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar territorio'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}


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
    public function getRegiones()
    {
        try {
            $regiones = Region::all();
            return response()->json($regiones, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Ubicacion][getRegiones] ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener regiones'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getProvincias(Request $request)
    {
        $ids = $request->query('region_ids');
        if (!$ids) {
            return response()->json(['message' => 'Se requieren IDs de regiones'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $list = is_string($ids) ? explode(',', $ids) : (array)$ids;
            $provincias = Provincia::whereIn('region_id', $list)->with('comunas')->get();
            return response()->json($provincias, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Ubicacion][getProvincias] ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener provincias'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getComunas(Request $request)
    {
        $ids = $request->query('provincia_ids');
        if (!$ids) {
            return response()->json(['message' => 'Se requieren IDs de provincias'], Response::HTTP_BAD_REQUEST);
        }
        try {
            $list = is_string($ids) ? explode(',', $ids) : (array)$ids;
            $comunas = Comuna::whereIn('provincia_id', $list)->get();
            return response()->json($comunas, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Ubicacion][getComunas] ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener comunas'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Territorio;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use App\Models\LineasDeIntervencion;
use App\Models\PlanIntervencion;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class TerritorioController extends Controller
{
    public function index()
    {
        try {
            $territorios = Territorio::with('linea')->get()->map(function ($t) {
                 
                // 1) Obtener los IDs, ya sea string JSON o array
                $regionIds    = is_string($t->region_id)
                                ? json_decode($t->region_id, true)
                                : ($t->region_id ?? []);
                $provinciaIds = is_string($t->provincia_id)
                                ? json_decode($t->provincia_id, true)
                                : ($t->provincia_id ?? []);
                $comunaIds    = is_string($t->comuna_id)
                                ? json_decode($t->comuna_id, true)
                                : ($t->comuna_id ?? []);

                // 2) Recuperar los modelos de ubicación
                $regiones   = Region::whereIn('id', $regionIds)->get();
                $provincias = Provincia::whereIn('id', $provinciaIds)->get();
                $comunas    = Comuna::whereIn('id', $comunaIds)->get();

                // 3) Obtener el ID de línea
                $lineaId = $t->linea?->id;

                // 4) Buscar el plan de intervención de esa línea
                $plan = null;
                if ($lineaId) {
                    $planModel = PlanIntervencion::where('linea_id', $lineaId)
                                    ->orderBy('id')
                                    ->first();
                    if ($planModel) {
                        $plan = [
                            'id'     => $planModel->id,
                            'nombre' => $planModel->nombre,
                        ];
                    }
                }

                // 5) Devolver todo, añadiendo 'plan_intervencion'
                    return [
                        'id'                => $t->id,
                        'nombre_territorio' => $t->nombre_territorio,
                        'cod_territorio'    => $t->cod_territorio,
                        'linea_id'          => $t->linea_id, // ✅ clave que necesita el filtro en el front
                        'linea'             => $t->linea
                                                ? ['id' => $t->linea->id, 'nombre' => $t->linea->nombre]
                                                : null,
                        'regiones'          => $regiones,
                        'provincias'        => $provincias,
                        'comunas'           => $comunas,
                        'region_nombres'    => $regiones->pluck('nombre')->join(', '),
                        'provincia_nombres' => $provincias->pluck('nombre')->join(', '),
                        'comuna_nombres'    => $comunas->pluck('nombre')->join(', '),
                        'plazas'            => $t->plazas,
                        'cuotas'            => [
                            'cuota_1' => $t->cuota_1,
                            'cuota_2' => $t->cuota_2,
                            'total'   => $t->total,
                        ],
                        'plan_intervencion' => $plan,
                    ];
            });

            return response()->json($territorios, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[Territorio][index] ' . $e->getMessage());
            return response()->json(
                ['message' => 'Error al listar territorios'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    public function show($id)
    {
        try {
            // 1) Buscamos el territorio (con su línea)
            $t = Territorio::with('linea')->findOrFail($id);
            // 2) Decodificamos JSON de regiones/provincias/comunas
            $regionIds    = is_string($t->region_id)    ? json_decode($t->region_id, true)    : ($t->region_id ?? []);
            $provinciaIds = is_string($t->provincia_id) ? json_decode($t->provincia_id, true) : ($t->provincia_id ?? []);
            $comunaIds    = is_string($t->comuna_id)    ? json_decode($t->comuna_id, true)    : ($t->comuna_id ?? []);

            $regiones   = Region::whereIn('id', $regionIds)->get();
            $provincias = Provincia::whereIn('id', $provinciaIds)->get();
            $comunas    = Comuna::whereIn('id', $comunaIds)->get();

            // 3) Calcular el plan de intervención
            $plan = null;
            if ($t->linea) {
                Log::info("[Territorio][show] Buscando plan para linea_id={$t->linea->id}");
                $planModel = PlanIntervencion::where('linea_id', $t->linea->id)
                                ->orderBy('id')
                                ->first();
                if ($planModel) {
                    $plan = [
                        'id'     => $planModel->id,
                        'nombre' => $planModel->nombre,
                    ];
                }
            }

            // 4) Armamos el payload completo
            $payload = [
                'id'                => $t->id,
                'nombre_territorio' => $t->nombre_territorio,
                'cod_territorio'    => $t->cod_territorio,
                'linea'             => $t->linea
                                        ? ['id' => $t->linea->id, 'nombre' => $t->linea->nombre]
                                        : null,
                'regiones'          => $regiones,
                'provincias'        => $provincias,
                'comunas'           => $comunas,
                'region_nombres'    => $regiones->pluck('nombre')->join(', '),
                'provincia_nombres' => $provincias->pluck('nombre')->join(', '),
                'comuna_nombres'    => $comunas->pluck('nombre')->join(', '),
                'plazas'            => $t->plazas,
                'cuotas'            => [
                    'cuota_1' => $t->cuota_1,
                    'cuota_2' => $t->cuota_2,
                    'total'   => $t->total,
                ],
                // bloque de plan de intervención
                'plan_intervencion' => $plan,
            ];

            return response()->json($payload, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error("[Territorio][show] " . $e->getMessage());
            return response()->json(
                ['message' => 'Territorio no encontrado o error interno'],
                Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

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
                ['total' => ($request->cuota_1 ?? 0) + ($request->cuota_2 ?? 0)]
            ));

            return response()->json($t, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('[Territorio][store] ' . $e->getMessage());
            return response()->json(['message' => 'Error al crear territorio'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

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
            $t->linea = LineasDeIntervencion::find($t->linea_id)?->nombre;

            return response()->json($t, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Territorio no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('[Territorio][update] ' . $e->getMessage());
            return response()->json(['message' => 'Error al actualizar territorio'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            $t = Territorio::findOrFail($id);
            $t->delete();
            return response()->json(['message' => 'Territorio eliminado correctamente'], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Territorio no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('[Territorio][destroy] ' . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar territorio'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

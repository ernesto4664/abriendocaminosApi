<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UsuariosInstitucion;
use App\Models\Provincia;
use App\Models\Comuna;
use App\Models\Territorio;
use App\Models\InstitucionEjecutora;


class UsuariosInstitucionController extends Controller
{
    public function index(Request $request)
    {
        try {
            // 1) Cargamos todos los usuarios con las relaciones base
            $usuarios = UsuariosInstitucion::with([
                'region',
                'provincia',
                'comuna',
                'institucion.territorio',
            ])->get();

            // 2) Para cada SEREMI, cargamos sus institucionesPivot
            //    (no necesitamos auth, lo hacemos sobre cada modelo)
            $seremis = $usuarios->filter(fn($u) => $u->rol === 'SEREMI');
            if ($seremis->isNotEmpty()) {
                // Eager-load pivot para esos IDs
                $seremiIds = $seremis->pluck('id')->toArray();
                $pivotData = UsuariosInstitucion::with('institucionesPivot.territorio')
                    ->whereIn('id', $seremiIds)
                    ->get()
                    ->pluck('institucionesPivot', 'id');
                
                // Asignamos al collection original
                $usuarios = $usuarios->map(function($u) use($pivotData) {
                    if ($u->rol === 'SEREMI') {
                        $u->institucionesPivot = $pivotData[$u->id] ?? [];
                    }
                    return $u;
                });
            }

            return response()->json($usuarios, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][index] ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener usuarios'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres'          => 'required|string|max:255',
            'apellidos'        => 'required|string|max:255',
            'rut'              => 'required|string|max:255|unique:usuarios_institucion,rut',
            'sexo'             => ['required', Rule::in(['M','F'])],
            'fecha_nacimiento' => 'required|date',
            'profesion'        => 'nullable|string',
            'email'            => 'required|email|max:255|unique:usuarios_institucion,email',
            'rol'              => ['required', Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
            'region_id'        => 'required|exists:regions,id',
            'provincia_id'     => ['nullable', Rule::requiredIf(fn() => $request->rol !== 'SEREMI'), 'exists:provincias,id'],
            'comuna_id'        => ['nullable', Rule::requiredIf(fn() => $request->rol !== 'SEREMI'), 'exists:comunas,id'],
            'institucion_id'   => ['nullable', Rule::requiredIf(fn() => $request->rol !== 'SEREMI'), 'exists:instituciones_ejecutoras,id'],
            'password'         => 'required|string|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $provinceIds = $comunaIds = $instIds = [];

        try {
            if ($validated['rol'] === 'SEREMI') {
                // 1) Provincias y comunas para pivots
                $provinceIds = Provincia::whereJsonContains('region_id', $validated['region_id'])
                                        ->pluck('id')->toArray();
                $comunaIds   = Comuna::whereIn('provincia_id', $provinceIds)
                                     ->pluck('id')->toArray();

                // 2) Instituciones: buscamos territorios cuyo json region_id contenga la región
                $territorioIds = Territorio::whereJsonContains('region_id', $validated['region_id'])
                                           ->pluck('id')->toArray();
                $instIds = InstitucionEjecutora::whereIn('territorio_id', $territorioIds)
                                               ->pluck('id')->toArray();

                unset($validated['provincia_id'], $validated['comuna_id'], $validated['institucion_id']);
            }

            // 3) Crear usuario
            $usuario = UsuariosInstitucion::create($validated);

            // 4) Sincronizar pivots (incluso arrays vacíos)
            $usuario->provincias()       ->sync($provinceIds);
            $usuario->comunas()          ->sync($comunaIds);
            $usuario->institucionesPivot()->sync($instIds);

            return response()->json($usuario, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][store] ' . $e->getMessage(), compact('provinceIds','comunaIds','instIds'));
            return response()->json(['message' => 'Error al crear usuario'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function show($id)
    {
        try {
            $user = UsuariosInstitucion::with(['region','provincia','comuna','institucion'])->findOrFail($id);
            return response()->json($user, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][show] ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener usuario'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $user = UsuariosInstitucion::findOrFail($id);

            $validated = $request->validate([
                'nombres'          => 'sometimes|string|max:255',
                'apellidos'        => 'sometimes|string|max:255',
                'rut'              => ['sometimes','string', Rule::unique('usuarios_institucion')->ignore($user->id)],
                'sexo'             => ['sometimes', Rule::in(['M','F'])],
                'fecha_nacimiento' => 'sometimes|date',
                'profesion'        => 'nullable|string',
                'email'            => ['sometimes','email', Rule::unique('usuarios_institucion')->ignore($user->id)],
                'rol'              => ['sometimes', Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
                'region_id'        => 'sometimes|exists:regions,id',
                'provincia_id'     => ['nullable', Rule::requiredIf(fn() => ($request->rol ?? $user->rol) !== 'SEREMI'), 'exists:provincias,id'],
                'comuna_id'        => ['nullable', Rule::requiredIf(fn() => ($request->rol ?? $user->rol) !== 'SEREMI'), 'exists:comunas,id'],
                'institucion_id'   => ['nullable', Rule::requiredIf(fn() => ($request->rol ?? $user->rol) !== 'SEREMI'), 'exists:instituciones_ejecutoras,id'],
                'password'         => 'nullable|string|min:8',
            ]);

            if (!empty($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            } else {
                unset($validated['password']);
            }

            $provinceIds = $comunaIds = $instIds = [];

            if (($validated['rol'] ?? $user->rol) === 'SEREMI') {
                $regionId = $validated['region_id'] ?? $user->region_id;
                $provinceIds = Provincia::whereJsonContains('region_id', $regionId)
                                        ->pluck('id')->toArray();
                $comunaIds   = Comuna::whereIn('provincia_id', $provinceIds)
                                     ->pluck('id')->toArray();

                $territorioIds = Territorio::whereJsonContains('region_id', $regionId)
                                           ->pluck('id')->toArray();
                $instIds = InstitucionEjecutora::whereIn('territorio_id', $territorioIds)
                                               ->pluck('id')->toArray();

                unset($validated['provincia_id'], $validated['comuna_id'], $validated['institucion_id']);
            }

            $user->update($validated);

            $user->provincias()       ->sync($provinceIds);
            $user->comunas()          ->sync($comunaIds);
            $user->institucionesPivot()->sync($instIds);

            return response()->json($user, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][update] ' . $e->getMessage(), compact('provinceIds','comunaIds','instIds'));
            return response()->json(['message' => 'Error al actualizar usuario'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    public function destroy($id)
    {
        try {
            UsuariosInstitucion::findOrFail($id)->delete();
            return response()->json(['message' => 'Usuario eliminado con éxito'], Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][destroy] ' . $e->getMessage());
            return response()->json(['message' => 'Error al eliminar usuario'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

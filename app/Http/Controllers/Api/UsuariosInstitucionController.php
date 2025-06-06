<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UsuariosInstitucion;

class UsuariosInstitucionController extends Controller
{
    public function index()
    {
        try {
            $usuarios = UsuariosInstitucion::with([
                'region',
                'provincia',
                'comuna',
                'institucion.territorio' // 💥 Esto incluye automáticamente los datos del territorio
            ])->get();

            return response()->json($usuarios, Response::HTTP_OK);
        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][index] ' . $e->getMessage());
            return response()->json(['message' => 'Error al obtener usuarios'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombres'         => 'required|string|max:255',
            'apellidos'       => 'required|string|max:255',
            'rut'             => 'required|string|max:255|unique:usuarios_institucion,rut',
            'sexo'            => ['required',Rule::in(['M','F'])],
            'fecha_nacimiento'=> 'required|date',
            'profesion'       => 'nullable|string',
            'email'           => 'required|email|max:255|unique:usuarios_institucion,email',
            'rol'             => ['required',Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
            'region_id'       => 'required|exists:regions,id',
            'provincia_id'    => 'required|exists:provincias,id',
            'comuna_id'       => 'required|exists:comunas,id',
            'institucion_id'  => 'required|exists:instituciones_ejecutoras,id',
            'password'        => 'required|string|min:8',
        ]);
        $validated['password'] = Hash::make($validated['password']);
        try {
            $usuario = UsuariosInstitucion::create($validated);
            return response()->json($usuario, Response::HTTP_CREATED);
        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][store] ' . $e->getMessage());
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
                'provincia_id'     => 'sometimes|exists:provincias,id',
                'comuna_id'        => 'sometimes|exists:comunas,id',
                'institucion_id'   => 'sometimes|exists:instituciones_ejecutoras,id',
                'password'         => 'nullable|string|min:8',
            ]);

            // 🔒 Si se envió una contraseña NO vacía, se hashea y se incluye
            if (array_key_exists('password', $validated)) {
                if (!empty($validated['password'])) {
                    $validated['password'] = Hash::make($validated['password']);
                } else {
                    unset($validated['password']); // ❌ No actualizar password si vino vacío
                }
            }

            $user->update($validated);

            return response()->json($user, Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json(['message' => 'Usuario no encontrado'], Response::HTTP_NOT_FOUND);
        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][update] ' . $e->getMessage());
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

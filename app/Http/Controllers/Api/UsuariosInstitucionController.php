<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UsuariosInstitucion;
use App\Models\InstitucionEjecutora;
use App\Models\LineasDeIntervencion;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use App\Models\MDSFApiResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UsuariosInstitucionController extends Controller
{
    // 🟢 Listar todos los usuarios de institución
    public function index()
    {
        $resp = new MDSFApiResponse();

        try {
            $resp->data = UsuariosInstitucion::with(['region', 'provincia', 'comuna', 'institucion'])->get();
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('[UsuariosInstitucion][index] ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener usuarios';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    // 🟢 Crear un nuevo usuario de institución
    public function store(Request $request)
    {
        $resp = new MDSFApiResponse();

        try {
            Log::info('[UsuariosInstitucion][store] Datos recibidos', $request->all());

            $validated = $request->validate([
                'nombres'         => 'required|string|max:255',
                'apellidos'       => 'required|string|max:255',
                'rut'             => 'required|string|unique:usuarios_institucion,rut|max:255',
                'sexo'            => ['required', Rule::in(['M','F'])],
                'fecha_nacimiento'=> 'required|date',
                'profesion'       => 'nullable|string',
                'email'           => 'required|email|unique:usuarios_institucion,email|max:255',
                'rol'             => ['required', Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
                'region_id'       => 'required|exists:regions,id',
                'provincia_id'    => 'required|exists:provincias,id',
                'comuna_id'       => 'required|exists:comunas,id',
                'institucion_id'  => 'required|exists:instituciones_ejecutoras,id',
                'password'        => 'required|string|min:8',
            ]);

            $validated['password'] = Hash::make($validated['password']);

            $usuario = UsuariosInstitucion::create($validated);

            Log::info('[UsuariosInstitucion][store] Usuario creado', ['id' => $usuario->id]);

            $resp->data    = $usuario;
            $resp->code    = 201;
            $resp->message = 'Usuario creado con éxito';
        } catch (\Exception $e) {
            Log::error('[UsuariosInstitucion][store] ' . $e->getMessage());
            $resp->code    = $e instanceof \Illuminate\Validation\ValidationException ? 422 : 500;
            $resp->message = $e instanceof \Illuminate\Validation\ValidationException
                              ? 'Errores de validación'
                              : 'Error al crear usuario';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    // 🟢 Obtener un usuario específico
    public function show($id)
    {
        $resp = new MDSFApiResponse();

        try {
            $usuario = UsuariosInstitucion::with(['region','provincia','comuna','institucion'])->find($id);
            if (!$usuario) {
                $resp->code    = 404;
                $resp->message = 'Usuario no encontrado';
            } else {
                $resp->data = $usuario;
                $resp->code = 200;
            }
        } catch (\Exception $e) {
            Log::error('[UsuariosInstitucion][show] ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener usuario';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    // 🟢 Actualizar un usuario
    public function update(Request $request, $id)
    {
        $resp = new MDSFApiResponse();

        try {
            $usuario = UsuariosInstitucion::find($id);
            if (!$usuario) {
                $resp->code    = 404;
                $resp->message = 'Usuario no encontrado';
                return $resp->json();
            }

            $validated = $request->validate([
                'nombres'         => 'sometimes|string|max:255',
                'apellidos'       => 'sometimes|string|max:255',
                'rut'             => ['sometimes','string',Rule::unique('usuarios_institucion')->ignore($usuario->id)],
                'sexo'            => ['sometimes',Rule::in(['M','F'])],
                'fecha_nacimiento'=> 'sometimes|date',
                'profesion'       => 'nullable|string',
                'email'           => ['sometimes','email',Rule::unique('usuarios_institucion')->ignore($usuario->id)],
                'rol'             => ['sometimes',Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
                'region_id'       => 'sometimes|exists:regions,id',
                'provincia_id'    => 'sometimes|exists:provincias,id',
                'comuna_id'       => 'sometimes|exists:comunas,id',
                'institucion_id'  => 'sometimes|exists:instituciones_ejecutoras,id',
                'password'        => 'nullable|string|min:8',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $usuario->update($validated);

            Log::info('[UsuariosInstitucion][update] Usuario actualizado', ['id' => $usuario->id]);

            $resp->data    = $usuario;
            $resp->code    = 200;
            $resp->message = 'Usuario actualizado con éxito';
        } catch (\Exception $e) {
            Log::error('[UsuariosInstitucion][update] ' . $e->getMessage());
            $resp->code    = $e instanceof \Illuminate\Validation\ValidationException ? 422 : 500;
            $resp->message = $e instanceof \Illuminate\Validation\ValidationException
                              ? 'Errores de validación'
                              : 'Error al actualizar usuario';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }

    // 🟢 Eliminar un usuario
    public function destroy($id)
    {
        $resp = new MDSFApiResponse();

        try {
            $usuario = UsuariosInstitucion::find($id);
            if (!$usuario) {
                $resp->code    = 404;
                $resp->message = 'Usuario no encontrado';
            } else {
                $usuario->delete();
                Log::info('[UsuariosInstitucion][destroy] Usuario eliminado', ['id' => $id]);
                $resp->code    = 200;
                $resp->message = 'Usuario eliminado con éxito';
            }
        } catch (\Exception $e) {
            Log::error('[UsuariosInstitucion][destroy] ' . $e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al eliminar usuario';
            $resp->error   = $e->getMessage();
        }

        return $resp->json();
    }
}

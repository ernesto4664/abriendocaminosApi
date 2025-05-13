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
    /**
     * Listar todos los usuarios de institución
     */
    public function index()
    {
        try {
            $usuarios = UsuariosInstitucion::with([
                'region', 'provincia', 'comuna', 'institucion'
            ])->get();

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $usuarios,
            ], Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][index] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener usuarios',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear un nuevo usuario de institución
     */
    public function store(Request $request)
    {
        Log::info('[UsuariosInstitucion][store] Datos recibidos', $request->all());

        $validated = $request->validate([
            'nombres'           => 'required|string|max:255',
            'apellidos'         => 'required|string|max:255',
            'rut'               => 'required|string|max:255|unique:usuarios_institucion,rut',
            'sexo'              => ['required', Rule::in(['M','F'])],
            'fecha_nacimiento'  => 'required|date',
            'profesion'         => 'nullable|string',
            'email'             => 'required|email|max:255|unique:usuarios_institucion,email',
            'rol'               => ['required', Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
            'region_id'         => 'required|exists:regions,id',
            'provincia_id'      => 'required|exists:provincias,id',
            'comuna_id'         => 'required|exists:comunas,id',
            'institucion_id'    => 'required|exists:instituciones_ejecutoras,id',
            'password'          => 'required|string|min:8',
        ]);

        try {
            $validated['password'] = Hash::make($validated['password']);
            $usuario = UsuariosInstitucion::create($validated);

            return response()->json([
                'code'    => Response::HTTP_CREATED,
                'message' => 'Usuario creado con éxito',
                'data'    => $usuario,
            ], Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][store] ' . $e->getMessage());

            $status = $e instanceof \Illuminate\Validation\ValidationException
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'code'    => $status,
                'message' => $e instanceof \Illuminate\Validation\ValidationException
                              ? 'Errores de validación'
                              : 'Error al crear usuario',
                'error'   => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * Obtener un usuario específico
     */
    public function show($id)
    {
        try {
            $usuario = UsuariosInstitucion::with([
                'region', 'provincia', 'comuna', 'institucion'
            ])->findOrFail($id);

            return response()->json([
                'code' => Response::HTTP_OK,
                'data' => $usuario,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Usuario no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][show] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al obtener usuario',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar un usuario
     */
    public function update(Request $request, $id)
    {
        try {
            $usuario = UsuariosInstitucion::findOrFail($id);

            $validated = $request->validate([
                'nombres'           => 'sometimes|string|max:255',
                'apellidos'         => 'sometimes|string|max:255',
                'rut'               => ['sometimes','string', Rule::unique('usuarios_institucion')->ignore($usuario->id)],
                'sexo'              => ['sometimes', Rule::in(['M','F'])],
                'fecha_nacimiento'  => 'sometimes|date',
                'profesion'         => 'nullable|string',
                'email'             => ['sometimes','email', Rule::unique('usuarios_institucion')->ignore($usuario->id)],
                'rol'               => ['sometimes', Rule::in(['SEREMI','COORDINADOR','PROFESIONAL'])],
                'region_id'         => 'sometimes|exists:regions,id',
                'provincia_id'      => 'sometimes|exists:provincias,id',
                'comuna_id'         => 'sometimes|exists:comunas,id',
                'institucion_id'    => 'sometimes|exists:instituciones_ejecutoras,id',
                'password'          => 'nullable|string|min:8',
            ]);

            if (isset($validated['password'])) {
                $validated['password'] = Hash::make($validated['password']);
            }

            $usuario->update($validated);

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Usuario actualizado con éxito',
                'data'    => $usuario,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Usuario no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][update] ' . $e->getMessage());

            $status = $e instanceof \Illuminate\Validation\ValidationException
                ? Response::HTTP_UNPROCESSABLE_ENTITY
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return response()->json([
                'code'    => $status,
                'message' => $e instanceof \Illuminate\Validation\ValidationException
                              ? 'Errores de validación'
                              : 'Error al actualizar usuario',
                'error'   => $e->getMessage(),
            ], $status);
        }
    }

    /**
     * Eliminar un usuario
     */
    public function destroy($id)
    {
        try {
            $usuario = UsuariosInstitucion::findOrFail($id);
            $usuario->delete();

            return response()->json([
                'code'    => Response::HTTP_OK,
                'message' => 'Usuario eliminado con éxito',
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'code'    => Response::HTTP_NOT_FOUND,
                'message' => 'Usuario no encontrado',
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('[UsuariosInstitucion][destroy] ' . $e->getMessage());

            return response()->json([
                'code'    => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => 'Error al eliminar usuario',
                'error'   => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

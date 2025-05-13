<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UsuariosInstitucion;

class AuthUsuariosInstitucionController extends Controller
{
    /**
     * 🔹 Registro de usuario de institución
     */
    public function register(Request $request)
    {
        // validación de inputs
        $data = $request->validate([
            'nombres'           => 'required|string|max:255',
            'apellidos'         => 'required|string|max:255',
            'rut'               => 'required|string|max:12|unique:usuarios_institucion,rut',
            'sexo'              => 'required|in:M,F',
            'fecha_nacimiento'  => 'required|date',
            'profesion'         => 'nullable|string',
            'email'             => 'required|email|unique:usuarios_institucion,email',
            'region_id'         => 'required|exists:regions,id',
            'provincia_id'      => 'required|exists:provincias,id',
            'comuna_id'         => 'required|exists:comunas,id',
            'institucion_id'    => 'required|exists:instituciones_ejecutoras,id',
            'password'          => 'required|string|min:6|confirmed',
        ]);

        try {
            $data['password'] = Hash::make($data['password']);
            $data['rol']      = 'PROFESIONAL';

            $usuario = UsuariosInstitucion::create($data);

            return response()->json($usuario, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('Error al registrar usuario de institución: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error interno al registrar el usuario'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 🔹 Inicio de sesión
     */
    public function login(Request $request)
    {
        // validación de inputs
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $usuario = UsuariosInstitucion::where('email', $request->email)->firstOrFail();

            if (! Hash::check($request->password, $usuario->password)) {
                throw new ValidationException(validator: null, response: null, customResponse: null);
            }

            $token = $usuario->createToken('authToken')->plainTextToken;

            return response()->json([
                'usuario' => $usuario,
                'token'   => $token,
            ], Response::HTTP_OK);

        } catch (ValidationException) {
            return response()->json([
                'message' => 'Las credenciales son incorrectas'
            ], Response::HTTP_UNPROCESSABLE_ENTITY);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Usuario no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error('Error en login de usuario de institución: ' . $e->getMessage());

            return response()->json([
                'message' => 'Error interno al iniciar sesión'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

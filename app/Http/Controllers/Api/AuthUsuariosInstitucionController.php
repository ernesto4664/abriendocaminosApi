<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsuariosInstitucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class AuthUsuariosInstitucionController extends Controller
{
    /**
     * 🔹 Registro de usuario de institución
     */
    public function register(Request $request)
    {
        $request->validate([
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
            'password'          => 'required|string|min:6|confirmed'
        ]);

        try {
            $data = $request->only([
                'nombres',
                'apellidos',
                'rut',
                'sexo',
                'fecha_nacimiento',
                'profesion',
                'email',
                'region_id',
                'provincia_id',
                'comuna_id',
                'institucion_id',
            ]);
            $data['password'] = Hash::make($request->password);
            $data['rol']      = 'PROFESIONAL';

            $usuario = UsuariosInstitucion::create($data);

            return response()->json([
                'code'    => 201,
                'message' => 'Usuario registrado correctamente',
                'data'    => $usuario,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error al registrar usuario de institución: ' . $e->getMessage());

            return response()->json([
                'code'    => 500,
                'message' => 'Error interno al registrar el usuario',
            ], 500);
        }
    }

    /**
     * 🔹 Inicio de sesión
     */
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        try {
            $usuario = UsuariosInstitucion::where('email', $request->email)->first();

            if (!$usuario || !Hash::check($request->password, $usuario->password)) {
                throw ValidationException::withMessages([
                    'email' => ['Las credenciales son incorrectas']
                ]);
            }

            $token = $usuario->createToken('authToken')->plainTextToken;

            return response()->json([
                'code'    => 200,
                'message' => 'Inicio de sesión exitoso',
                'data'    => [
                    'usuario' => $usuario,
                    'token'   => $token
                ],
            ], 200);

        } catch (ValidationException $ve) {
            return response()->json([
                'code'    => 422,
                'message' => 'Credenciales inválidas',
                'errors'  => $ve->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error en login de usuario de institución: ' . $e->getMessage());

            return response()->json([
                'code'    => 500,
                'message' => 'Error interno al iniciar sesión',
            ], 500);
        }
    }
}

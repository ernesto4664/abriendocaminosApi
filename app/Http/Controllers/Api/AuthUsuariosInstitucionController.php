<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsuariosInstitucion;
use App\Models\MDSFApiResponse;
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
        $respuesta = new MDSFApiResponse();

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
            $data = $request->all();
            $data['password'] = Hash::make($data['password']);
            $data['rol']      = 'PROFESIONAL';

            $usuario = UsuariosInstitucion::create($data);

            $respuesta->data    = $usuario;
            $respuesta->code    = 201;
            $respuesta->message = 'Usuario registrado correctamente';
        } catch (\Exception $e) {
            Log::error('Error al registrar usuario de institución: '.$e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error interno al registrar el usuario';
        }

        return $respuesta->json();
    }

    /**
     * 🔹 Inicio de sesión
     */
    public function login(Request $request)
    {
        $respuesta = new MDSFApiResponse();

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

            $respuesta->data = [
                'usuario' => $usuario,
                'token'   => $token
            ];
            $respuesta->code    = 200;
            $respuesta->message = 'Inicio de sesión exitoso';
        } catch (ValidationException $ve) {
            // errores de validación de credenciales
            $respuesta->code    = 422;
            $respuesta->message = 'Credenciales inválidas';
            $respuesta->errors  = $ve->errors();
        } catch (\Exception $e) {
            Log::error('Error en login de usuario de institución: '.$e->getMessage());
            $respuesta->code    = 500;
            $respuesta->message = 'Error interno al iniciar sesión';
        }

        return $respuesta->json();
    }
}

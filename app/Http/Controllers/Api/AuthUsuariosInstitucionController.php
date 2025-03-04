<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsuariosInstitucion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthUsuariosInstitucionController extends Controller
{
    /**
     * 🔹 Registro de usuario de institución
     */
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'nombres' => 'required|string|max:255',
            'apellidos' => 'required|string|max:255',
            'rut' => 'required|string|max:12|unique:usuarios_institucion,rut',
            'sexo' => 'required|in:M,F',
            'fecha_nacimiento' => 'required|date',
            'profesion' => 'nullable|string',
            'email' => 'required|email|unique:usuarios_institucion,email',
            'region_id' => 'required|exists:regions,id',
            'provincia_id' => 'required|exists:provincias,id',
            'comuna_id' => 'required|exists:comunas,id',
            'institucion_id' => 'required|exists:instituciones_ejecutoras,id',
            'password' => 'required|string|min:6|confirmed'
        ]);

        $validatedData['password'] = Hash::make($validatedData['password']);
        $validatedData['rol'] = 'PROFESIONAL'; // Por defecto todos se registran como PROFESIONAL

        $usuario = UsuariosInstitucion::create($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Usuario de institución registrado correctamente',
            'data' => $usuario
        ], 201);
    }

    /**
     * 🔹 Inicio de sesión
     */
    public function login(Request $request)
    {
        $validatedData = $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        $usuario = UsuariosInstitucion::where('email', $validatedData['email'])->first();

        if (!$usuario || !Hash::check($validatedData['password'], $usuario->password)) {
            throw ValidationException::withMessages(['email' => ['Las credenciales son incorrectas']]);
        }

        $token = $usuario->createToken('authToken')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'usuario' => $usuario,
                'token' => $token
            ]
        ]);
    }
}

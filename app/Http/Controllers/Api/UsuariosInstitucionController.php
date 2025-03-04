<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UsuariosInstitucion;
use App\Models\InstitucionEjecutora;
use App\Models\LineasDeIntervencion;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class UsuariosInstitucionController extends Controller
{
    // 🟢 Listar todos los usuarios de institución
    public function index()
    {
        $usuarios = UsuariosInstitucion::with(['region', 'provincia', 'comuna', 'institucion'])->get();
        return response()->json($usuarios);
    }

    // 🟢 Crear un nuevo usuario de institución
    public function store(Request $request)
    {
        try {
            Log::info("📌 Iniciando creación de usuario", ['data' => $request->all()]);
    
            $validatedData = $request->validate([
                'nombres' => 'required|string|max:255',
                'apellidos' => 'required|string|max:255',
                'rut' => 'required|string|unique:usuarios_institucion,rut|max:255',
                'sexo' => ['required', Rule::in(['M', 'F'])],
                'fecha_nacimiento' => 'required|date',
                'profesion' => 'nullable|string',
                'email' => 'required|email|unique:usuarios_institucion,email|max:255',
                'rol' => ['required', Rule::in(['SEREMI', 'COORDINADOR', 'PROFESIONAL'])],
                'region_id' => 'required|exists:regions,id',
                'provincia_id' => 'required|exists:provincias,id',
                'comuna_id' => 'required|exists:comunas,id',
                'institucion_id' => 'required|exists:instituciones_ejecutoras,id',
                'password' => 'required|string|min:8',
            ]);
    
            Log::info("✅ Validación completada con éxito", ['validatedData' => $validatedData]);
    
            $usuario = UsuariosInstitucion::create([
                'nombres' => $validatedData['nombres'],
                'apellidos' => $validatedData['apellidos'],
                'rut' => $validatedData['rut'],
                'sexo' => $validatedData['sexo'],
                'fecha_nacimiento' => $validatedData['fecha_nacimiento'],
                'profesion' => $validatedData['profesion'],
                'email' => $validatedData['email'],
                'rol' => $validatedData['rol'],
                'region_id' => $validatedData['region_id'],
                'provincia_id' => $validatedData['provincia_id'],
                'comuna_id' => $validatedData['comuna_id'],
                'institucion_id' => $validatedData['institucion_id'],
                'password' => Hash::make($validatedData['password']),
            ]);
    
            Log::info("🎉 Usuario creado con éxito", ['usuario' => $usuario]);
    
            return response()->json(['message' => 'Usuario creado con éxito', 'usuario' => $usuario], 201);
        } catch (\Exception $e) {
            Log::error("❌ Error al crear usuario", [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ]);
    
            return response()->json(['error' => 'Error interno del servidor', 'message' => $e->getMessage()], 500);
        }
    }
    

    // 🟢 Obtener un usuario específico
    public function show($id)
    {
        $usuario = UsuariosInstitucion::with(['region', 'provincia', 'comuna', 'institucion'])->find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        return response()->json($usuario);
    }

    // 🟢 Actualizar un usuario
    public function update(Request $request, $id)
    {
        $usuario = UsuariosInstitucion::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $request->validate([
            'nombres' => 'sometimes|string|max:255',
            'apellidos' => 'sometimes|string|max:255',
            'rut' => ['sometimes', 'string', Rule::unique('usuarios_institucion')->ignore($usuario->id)],
            'sexo' => ['sometimes', Rule::in(['M', 'F'])],
            'fecha_nacimiento' => 'sometimes|date',
            'profesion' => 'nullable|string',
            'email' => ['sometimes', 'email', Rule::unique('usuarios_institucion')->ignore($usuario->id)],
            'rol' => ['sometimes', Rule::in(['SEREMI', 'COORDINADOR', 'PROFESIONAL'])],
            'region_id' => 'sometimes|exists:regions,id',
            'provincia_id' => 'sometimes|exists:provincias,id',
            'comuna_id' => 'sometimes|exists:comunas,id',
            'institucion_id' => 'sometimes|exists:instituciones_ejecutoras,id',
            'password' => 'nullable|string|min:8',
        ]);

        if ($request->has('password')) {
            $request->merge(['password' => Hash::make($request->password)]);
        }

        $usuario->update($request->all());

        return response()->json(['message' => 'Usuario actualizado con éxito', 'usuario' => $usuario]);
    }

    // 🟢 Eliminar un usuario
    public function destroy($id)
    {
        $usuario = UsuariosInstitucion::find($id);

        if (!$usuario) {
            return response()->json(['error' => 'Usuario no encontrado'], 404);
        }

        $usuario->delete();
        return response()->json(['message' => 'Usuario eliminado con éxito']);
    }
}
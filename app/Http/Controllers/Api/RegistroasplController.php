<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Registro_Aspl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class RegistroasplController extends Controller
{
    // Listar todos
    public function index()
    {
        Log::info('Listado de Personas Privadas de Libertad solicitado.');

        return response()->json([
            'data' => Registro_Aspl::all()
        ]);
    }

    // Crear nuevo registro
public function store(Request $request)
{
// Copiar para que la validación espere 'asignar_nna'
$request->merge(['asignar_nna' => $request->input('asignar_nna_id')]);

$validated = $request->validate([
    'rut_ppl' => 'required|numeric',
    'dv_ppl' => 'required|string|max:1',
    'asignar_nna' => 'required|integer',
    'nombres_ppl' => 'required|string',
    'apellidos_ppl' => 'required|string',
    'sexo_ppl' => 'required|string|in:M,F',
    'centro_penal' => 'required|string',
    'region_penal' => 'required',
    'nacionalidad_ppl' => 'required|string',
    'participa_programa' => 'required|boolean',
    'motivo_no_participa' => 'nullable|string',
]);

// Crear con la data validada
$ppl = Registro_Aspl::create($validated);


    Log::info('Nuevo registro creado', ['id' => $ppl->id, 'rut_ppl' => $ppl->rut_ppl]);

    return response()->json([
        'message' => 'Registro creado exitosamente',
        'data' => $ppl
    ], 201);
}


    // Mostrar un registro
    public function show($id)
    {
        $ppl = Registro_Aspl::findOrFail($id);

        Log::info('Registro consultado', ['id' => $id]);

        return response()->json(['data' => $ppl]);
    }

    // Actualizar registro
    public function update(Request $request, $id)
    {
        $ppl = Registro_Aspl::findOrFail($id);

        $validated = $request->validate([
            'rut_ppl' => 'sometimes|required|string|max:20',
            'dv_ppl' => 'sometimes|required|string|max:1',
            'asignar_nna_id' => 'sometimes|required|integer',
            'nombres_ppl' => 'sometimes|required|string|max:255',
            'apellidos_ppl' => 'sometimes|required|string|max:255',
            'sexo_ppl' => 'sometimes|required|in:M,F',
            'centro_penal' => 'sometimes|required|string|max:255',
            'region_penal' => 'sometimes|required|string|max:255',
            'nacionalidad_ppl' => 'sometimes|required|string|max:255',
            'participa_programa' => 'sometimes|required|boolean',
            'motivo_no_participa' => 'nullable|string',
        ]);

        $ppl->update($validated);

        Log::info('Registro actualizado', ['id' => $id, 'datos_actualizados' => $validated]);

        return response()->json([
            'message' => 'Registro actualizado exitosamente',
            'data' => $ppl
        ]);
    }

    // Eliminar registro
    public function destroy($id)
    {
        $ppl = Registro_Aspl::findOrFail($id);
        $ppl->delete();

        Log::info('Registro eliminado', ['id' => $id]);

        return response()->json([
            'message' => 'Registro eliminado exitosamente'
        ]);
    }
}

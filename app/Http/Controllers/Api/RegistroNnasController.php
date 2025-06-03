<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RegistroNnas;
use App\Models\UsuariosInstitucion;



class RegistroNnasController extends Controller
{
public function store(Request $request)
{
    Log::info('Iniciando validación de datos para registro NNA', ['request' => $request->all()]);

    // Convertir participa_programa a booleano si llega como string
    $request->merge([
        'participa_programa' => filter_var($request->input('participa_programa'), FILTER_VALIDATE_BOOLEAN)
    ]);

    // Reglas de validación base
    $rules = [
        'profesional' => 'required|integer',
        'institucion' => 'required|integer',
        'rut' => 'required|string|max:12',
        'dv' => 'required|string|max:1',
        'vias_ingreso' => 'required|string',
        'nombres' => 'required|string|max:100',
        'apellidos' => 'required|string|max:100',
        'edad' => 'required|integer|min:0|max:120',
        'sexo' => 'required|string|in:M,F',
        'parentesco_aspl' => 'required|string|max:50',
        'parentesco_cp' => 'required|string|max:50',
        'nacionalidad' => 'required|string|max:50',
        'participa_programa' => 'required|boolean',
    ];

    // Validaciones condicionales
    if ($request->participa_programa == false) {
        $rules['motivo_no_participa'] = 'required|string|max:255';
    }

    if ($request->participa_programa == true) {
        $rules['documento_firmado'] = 'required|file|mimes:pdf,jpg,jpeg,png|max:10240'; // Máx 10MB
    }

    $validated = $request->validate($rules);

    Log::info('Datos validados correctamente', ['validated' => $validated]);

    // Guardar el archivo si existe
    $pathDocumento = null;

    if ($request->hasFile('documento_firmado')) {
        $archivo = $request->file('documento_firmado');
        $nombreArchivo = time() . '_' . $archivo->getClientOriginalName();
        $pathDocumento = $archivo->storeAs('documentos_nna', $nombreArchivo, 'public');
        Log::info('Documento firmado guardado correctamente', ['ruta' => $pathDocumento]);
    }

    // Crear el registro en la base de datos
    $registro = RegistroNnas::create([
        'profesional_id' => $validated['profesional'],
        'institucion_id' => $validated['institucion'],
        'rut' => $validated['rut'],
        'dv' => $validated['dv'],
        'vias_ingreso' => $validated['vias_ingreso'],
        'nombres' => $validated['nombres'],
        'apellidos' => $validated['apellidos'],
        'edad' => $validated['edad'],
        'sexo' => $validated['sexo'],
        'parentesco_aspl' => $validated['parentesco_aspl'],
        'parentesco_cp' => $validated['parentesco_cp'],
        'nacionalidad' => $validated['nacionalidad'],
        'participa_programa' => $validated['participa_programa'],
        'motivo_no_participa' => $validated['motivo_no_participa'] ?? null,
        'documento_firmado' => $pathDocumento,
    ]);

    Log::info('Registro NNA creado correctamente', ['registro' => $registro]);

    return response()->json([
        'message' => 'Registro de NNA creado correctamente',
        'data' => $registro
    ], 201);
}
public function getNna()
{
    $nna = RegistroNnas::select('id', 'nombres', 'apellidos')->get();

    $nna = $nna->map(function ($item) {
        return [
            'id' => $item->id,
            'nombre' => $item->nombres . ' ' . $item->apellidos,
        ];
    });

    return response()->json(['data' => $nna], 200);
}
public function profesionalesPorRegion($regionId)
{
    try {
        $profesionales = UsuariosInstitucion::where('region_id', $regionId)->get();

        return response()->json($profesionales);
    } catch (\Exception $e) {
        \Log::error('Error al obtener profesionales por región: ' . $e->getMessage());
        return response()->json(['error' => 'Error interno del servidor'], 500);
    }
}


}

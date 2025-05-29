<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\Registro_Aspl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
use App\Models\RegistroCuidador;
use Illuminate\Support\Facades\Storage;


class RegistroCuidadorController extends Controller
{
public function store(Request $request)
{
    Log::info('Intentando registrar nuevo cuidador', ['request_data' => $request->all()]);

    $validated = $request->validate([
        'rut' => 'required|string',
        'dv' => 'required|string|max:1',
        'nombres' => 'required|string',
        'apellidos' => 'required|string',
        'asignarNna' => 'required|integer',
        'sexo' => 'required|string|in:M,F',
        'edad' => 'required|integer|min:0',
        'parentescoAspl' => 'required|string',
        'parentescoNna' => 'required|string',
        'nacionalidad' => 'required|string',
        'participaPrograma' => 'required|boolean',
        'motivoNoParticipa' => 'nullable|string',
        'documentoFirmado' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:2048', // max 2MB
    ]);

    // Guardar archivo si existe
    $pathDocumentoFirmado = null;
    if ($request->hasFile('documentoFirmado')) {
        $pathDocumentoFirmado = $request->file('documentoFirmado')->store('documentos_firmados', 'public');
    }

    // Crear nuevo registro cuidador
    $cuidador = new RegistroCuidador();
    $cuidador->rut = $validated['rut'];
    $cuidador->dv = $validated['dv'];
    $cuidador->nombres = $validated['nombres'];
    $cuidador->apellidos = $validated['apellidos'];
    $cuidador->asignar_nna = $validated['asignarNna'];
    $cuidador->sexo = $validated['sexo'];
    $cuidador->edad = $validated['edad'];
    $cuidador->parentesco_aspl = $validated['parentescoAspl'];
    $cuidador->parentesco_nna = $validated['parentescoNna'];
    $cuidador->nacionalidad = $validated['nacionalidad'];
    $cuidador->participa_programa = $validated['participaPrograma'];
    $cuidador->motivo_no_participa = $validated['motivoNoParticipa'];
    $cuidador->documento_firmado = $pathDocumentoFirmado; // Guarda la ruta del archivo si hay
    $cuidador->save();

    return response()->json(['message' => 'Cuidador registrado correctamente'], Response::HTTP_CREATED);
}

}

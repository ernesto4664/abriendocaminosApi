<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstitucionEjecutora;
use App\Models\LineasDeIntervencion;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class InstitucionEjecutoraController extends Controller {

    public function index(Request $request)
    {
        $query = InstitucionEjecutora::with(['planDeIntervencion', 'territorio']);
    
        // 🔹 Si se recibe region_id en la URL, filtramos
        if ($request->has('region_id')) {
            $regionId = $request->region_id;
    
            // 🔹 Filtramos por instituciones cuyo territorio tenga la región especificada
            $query->whereHas('territorio', function ($q) use ($regionId) {
                $q->whereJsonContains('region_id', (int) $regionId);
            });
        }
    
        $instituciones = $query->get()->map(function ($institucion) {
            if ($institucion->territorio) {
                $institucion->territorio->regiones = $institucion->territorio->regiones;
                $institucion->territorio->provincias = $institucion->territorio->provincias;
                $institucion->territorio->comunas = $institucion->territorio->comunas;
            }
            return $institucion;
        });
    
        return response()->json($instituciones, 200);
    }
    
    public function store(Request $request) {
        Log::info('📌 Intentando crear institución con datos:', $request->all());
    
        $request->validate([
            'nombre_fantasia' => 'required|string|max:255',
            'nombre_legal' => 'required|string|max:255',
            'rut' => 'required|string|max:20|',
            'telefono' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'territorio_id' => 'required|exists:territorios,id',
            'periodo_registro_desde' => 'required|date',
            'periodo_registro_hasta' => 'required|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde' => 'required|date',
            'periodo_seguimiento_hasta' => 'required|date|after_or_equal:periodo_seguimiento_desde'
        ]);
    
        try {
            $institucion = InstitucionEjecutora::create($request->all());
            Log::info('✅ Institución creada con éxito:', $institucion->toArray());
            return response()->json($institucion, 201);
        } catch (\Exception $e) {
            Log::error('❌ Error al crear institución:', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error interno al crear la institución', 'detalle' => $e->getMessage()], 500);
        }
    }
    
    public function show($id)
    {
        // Buscar la institución con sus relaciones
        $institucion = InstitucionEjecutora::with(['planDeIntervencion', 'territorio'])
            ->findOrFail($id);
    
        // Verificar si tiene un territorio asociado y cargar manualmente regiones, provincias y comunas
        if ($institucion->territorio) {
            $institucion->territorio->regiones = $institucion->territorio->regiones;
            $institucion->territorio->provincias = $institucion->territorio->provincias;
            $institucion->territorio->comunas = $institucion->territorio->comunas;
        }
    
        return response()->json($institucion, 200);
    }
    

    public function update(Request $request, $id) {

        $institucion = InstitucionEjecutora::findOrFail($id);
        $institucion->update($request->all());

        return response()->json($institucion, 200);
    }

    public function destroy($id) {
        
        InstitucionEjecutora::destroy($id);
        return response()->json(['message' => 'Institución ejecutora eliminada correctamente'], 200);
    }
}

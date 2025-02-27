<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;
use Illuminate\Support\Facades\Log;

class UbicacionController extends Controller
{
    // Obtener todas las regiones
    public function getRegiones()
    {
        $regiones = Region::all();
        return response()->json($regiones);
    }

    // Obtener provincias según una o varias regiones
    public function getProvincias(Request $request)
    {
      //  Log::info('📌 [getProvincias] Método ejecutado');
    
        // Obtener los IDs como array desde query params
        $regionIds = $request->query('region_ids');
      //  Log::info('🔍 Parámetro recibido region_ids:', ['region_ids' => $regionIds]);
    
        if (!$regionIds) {
        //    Log::error('🚨 No se han enviado regiones');
            return response()->json(['error' => 'No se han enviado regiones'], 400);
        }
    
        // Convertir a array si viene como string separado por comas
        $regionIdsArray = is_string($regionIds) ? explode(',', $regionIds) : (array) $regionIds;
      //  Log::info('📝 Array de regiones procesado:', ['regionIdsArray' => $regionIdsArray]);
    
        // Buscar provincias filtradas
        $provincias = Provincia::whereIn('region_id', $regionIdsArray)->with('comunas')->get();
      //  Log::info('🔎 Provincias encontradas:', ['total' => count($provincias)]);
    
        if ($provincias->isEmpty()) {
         //   Log::error('❌ No se encontraron provincias para los region_ids proporcionados:', ['regionIdsArray' => $regionIdsArray]);
            return response()->json(['error' => 'No se encontraron provincias'], 404);
        }
    
        return response()->json($provincias);
    }
    
    // Obtener comunas según una o varias provincias
    public function getComunas(Request $request)
    {
       // Log::info('📌 [getComunas] Método ejecutado');
    
        // Obtener los IDs como array desde query params
        $provinciaIds = $request->query('provincia_ids');
       // Log::info('🔍 Parámetro recibido provincia_ids:', ['provincia_ids' => $provinciaIds]);
    
        if (!$provinciaIds) {
           // Log::error('🚨 No se han proporcionado IDs de provincia');
            return response()->json(['error' => 'No se han proporcionado IDs de provincia'], 400);
        }
    
        // Convertir a array si viene como string separado por comas
        $provinciaIdsArray = is_string($provinciaIds) ? explode(',', $provinciaIds) : (array) $provinciaIds;
       // Log::info('📝 Array de provincias procesado:', ['provinciaIdsArray' => $provinciaIdsArray]);
    
        // Buscar comunas relacionadas
        $comunas = Comuna::whereIn('provincia_id', $provinciaIdsArray)->get();
      //  Log::info('🔎 Comunas encontradas:', ['total' => count($comunas)]);
    
        if ($comunas->isEmpty()) {
           // Log::error('❌ No se encontraron comunas para los provincia_ids proporcionados:', ['provinciaIdsArray' => $provinciaIdsArray]);
            return response()->json(['error' => 'No se encontraron comunas'], 404);
        }
    
        return response()->json($comunas);
    }
       
}

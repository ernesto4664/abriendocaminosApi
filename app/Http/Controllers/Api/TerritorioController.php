<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Territorio;
use App\Models\Region;
use App\Models\Provincia;
use App\Models\Comuna;

use Illuminate\Support\Facades\Log;

class TerritorioController extends Controller
{
    /**
     * Listar todos los territorios con sus relaciones.
     */
    public function index()
    {
        $territorios = Territorio::all();
    
        foreach ($territorios as $territorio) {
            // Convertimos JSON a arrays y aseguramos que sean números enteros
            $regionIds = is_array($territorio->region_id) ? array_map('intval', $territorio->region_id) : json_decode($territorio->region_id, true) ?? [];
            $provinciaIds = is_array($territorio->provincia_id) ? array_map('intval', $territorio->provincia_id) : json_decode($territorio->provincia_id, true) ?? [];
            $comunaIds = is_array($territorio->comuna_id) ? array_map('intval', $territorio->comuna_id) : json_decode($territorio->comuna_id, true) ?? [];
    
            // Depuración en el log para verificar valores
          //  \Log::info("🔎 Regiones: " . json_encode($regionIds));
          //  \Log::info("🔎 Provincias: " . json_encode($provinciaIds));
          //  \Log::info("🔎 Comunas: " . json_encode($comunaIds));
    
            // Consultamos la base de datos y asignamos los resultados
            $territorio->regiones = Region::whereIn('id', $regionIds)->get(['id', 'nombre'])->toArray();
            $territorio->provincias = Provincia::whereIn('id', $provinciaIds)->get(['id', 'nombre'])->toArray();
            $territorio->comunas = Comuna::whereIn('id', $comunaIds)->get(['id', 'nombre'])->toArray();
        }
    
        return response()->json($territorios);
    }
    
     /**
     * Obtener un territorio por su ID.
     */
    public function show($id)
    {
     //   Log::info('📌 [show] Obteniendo territorio', ['territorio_id' => $id]);
    
        $territorio = Territorio::find($id);
    
        if (!$territorio) {
          //  Log::error('❌ [show] Territorio no encontrado', ['territorio_id' => $id]);
            return response()->json(['error' => 'Territorio no encontrado'], 404);
        }
    
        // Obtener nombres de las comunas, provincias y regiones
        $territorio->comunas = Comuna::whereIn('id', $territorio->comuna_id)->get(['id', 'nombre']);
        $territorio->provincias = Provincia::whereIn('id', $territorio->provincia_id)->get(['id', 'nombre']);
        $territorio->regiones = Region::whereIn('id', $territorio->region_id)->get(['id', 'nombre']);
    
     //   Log::info('✅ [show] Territorio obtenido correctamente', ['territorio' => $territorio]);
    
        return response()->json($territorio);
    }
    

    /**
     * Crear un nuevo territorio.
     */
    public function store(Request $request)
    {
       // Log::info('📌 [store] Creando territorio', ['data' => $request->all()]);
    
        $request->validate([
            'nombre_territorio' => 'required|string|max:255',
            'cod_territorio' => 'required|integer',
            'comuna_id' => 'required|array',
            'provincia_id' => 'required|array',
            'region_id' => 'required|array',
            'plazas' => 'nullable|integer',
            'linea' => 'required|string|max:50',
            'cuota_1' => 'nullable|numeric',
            'cuota_2' => 'nullable|numeric',
        ]);
    
        // Guardar los arrays directamente SIN `json_encode()`
        $territorio = Territorio::create([
            'nombre_territorio' => $request->nombre_territorio,
            'cod_territorio' => $request->cod_territorio,
            'comuna_id' => $request->comuna_id, // No usar json_encode()
            'provincia_id' => $request->provincia_id, // No usar json_encode()
            'region_id' => $request->region_id, // No usar json_encode()
            'plazas' => $request->plazas,
            'linea' => $request->linea,
            'cuota_1' => $request->cuota_1,
            'cuota_2' => $request->cuota_2,
            'total' => ($request->cuota_1 ?? 0) + ($request->cuota_2 ?? 0),
        ]);
    
       // Log::info('✅ [store] Territorio creado correctamente', ['territorio' => $territorio]);
    
        return response()->json($territorio, 201);
    }
    
    /**
     * Actualizar un territorio.
     */
    public function update(Request $request, $id)
    {
       // Log::info('📌 [update] Actualizando territorio', ['territorio_id' => $id, 'data' => $request->all()]);
    
        $territorio = Territorio::find($id);
    
        if (!$territorio) {
         //   Log::error('❌ [update] Territorio no encontrado', ['territorio_id' => $id]);
            return response()->json(['error' => 'Territorio no encontrado'], 404);
        }
    
        $request->validate([
            'nombre_territorio' => 'sometimes|string|max:255',
            'cod_territorio' => 'sometimes|integer',
            'comuna_id' => 'sometimes|array',
            'provincia_id' => 'sometimes|array',
            'region_id' => 'sometimes|array',
            'plazas' => 'nullable|integer',
            'linea' => 'sometimes|string|max:50',
            'cuota_1' => 'nullable|numeric',
            'cuota_2' => 'nullable|numeric',
        ]);
    
        // Asegurar que los datos se almacenen correctamente como JSON sin afectar los arrays ya existentes
        $territorio->update([
            'nombre_territorio' => $request->nombre_territorio ?? $territorio->nombre_territorio,
            'cod_territorio' => $request->cod_territorio ?? $territorio->cod_territorio,
            'comuna_id' => $request->has('comuna_id') ? array_map('intval', $request->comuna_id) : $territorio->comuna_id,
            'provincia_id' => $request->has('provincia_id') ? array_map('intval', $request->provincia_id) : $territorio->provincia_id,
            'region_id' => $request->has('region_id') ? array_map('intval', $request->region_id) : $territorio->region_id,
            'plazas' => $request->plazas ?? $territorio->plazas,
            'linea' => $request->linea ?? $territorio->linea,
            'cuota_1' => $request->cuota_1 ?? $territorio->cuota_1,
            'cuota_2' => $request->cuota_2 ?? $territorio->cuota_2,
            'total' => ($request->cuota_1 ?? $territorio->cuota_1 ?? 0) + ($request->cuota_2 ?? $territorio->cuota_2 ?? 0),
        ]);
    
        // Obtener nombres de las comunas, provincias y regiones después de actualizar
        $territorio->comunas = Comuna::whereIn('id', $territorio->comuna_id)->get(['id', 'nombre']);
        $territorio->provincias = Provincia::whereIn('id', $territorio->provincia_id)->get(['id', 'nombre']);
        $territorio->regiones = Region::whereIn('id', $territorio->region_id)->get(['id', 'nombre']);
    
       // Log::info('✅ [update] Territorio actualizado correctamente', ['territorio' => $territorio]);
    
        return response()->json($territorio);
    }
    
    /**
     * Eliminar un territorio sin eliminación en cascada.
     */
    public function destroy($id)
    {
        $territorio = Territorio::find($id);

        if (!$territorio) {
            return response()->json(['error' => 'Territorio no encontrado'], 404);
        }

        $territorio->delete();
        return response()->json(['message' => 'Territorio eliminado correctamente'], 200);
    }
}

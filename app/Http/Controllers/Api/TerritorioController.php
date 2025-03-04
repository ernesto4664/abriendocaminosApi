<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Territorio;
use App\Models\LineasDeIntervencion;
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
        $territorios = Territorio::with('linea')->get();
    
        foreach ($territorios as $territorio) {
            $territorio->regiones = $territorio->regiones;
            $territorio->provincias = $territorio->provincias;
            $territorio->comunas = $territorio->comunas;
            $territorio->linea = $territorio->linea ? $territorio->linea->nombre : 'Sin asignar'; // ✅ Devuelve solo el nombre
        }
    
        return response()->json($territorios);
    }
      
    public function show($id)
    {
        $territorio = Territorio::with('linea')->find($id);
    
        if (!$territorio) {
            return response()->json(['error' => 'Territorio no encontrado'], 404);
        }
    
        $territorio->regiones = $territorio->regiones;
        $territorio->provincias = $territorio->provincias;
        $territorio->comunas = $territorio->comunas;
        $territorio->linea = $territorio->linea ? $territorio->linea->nombre : 'Sin asignar';
    
        return response()->json($territorio);
    }
    
    
    
    /**
     * Crear un nuevo territorio.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre_territorio' => 'required|string|max:255',
            'cod_territorio' => 'required|integer',
            'comuna_id' => 'required|array',
            'provincia_id' => 'required|array',
            'region_id' => 'required|array',
            'plazas' => 'nullable|integer',
            'linea_id' => 'required|integer', // ✅ Se usa línea_id
            'cuota_1' => 'nullable|numeric',
            'cuota_2' => 'nullable|numeric',
        ]);

        $territorio = Territorio::create([
            'nombre_territorio' => $request->nombre_territorio,
            'cod_territorio' => $request->cod_territorio,
            'comuna_id' => $request->comuna_id,
            'provincia_id' => $request->provincia_id,
            'region_id' => $request->region_id,
            'plazas' => $request->plazas,
            'linea_id' => $request->linea_id, // ✅ Se usa línea_id
            'cuota_1' => $request->cuota_1,
            'cuota_2' => $request->cuota_2,
            'total' => ($request->cuota_1 ?? 0) + ($request->cuota_2 ?? 0),
        ]);

        return response()->json($territorio, 201);
    }

    /**
     * Actualizar un territorio.
     */
    public function update(Request $request, $id)
    {
        $territorio = Territorio::find($id);

        if (!$territorio) {
            return response()->json(['error' => 'Territorio no encontrado'], 404);
        }

        $request->validate([
            'nombre_territorio' => 'sometimes|string|max:255',
            'cod_territorio' => 'sometimes|integer',
            'comuna_id' => 'sometimes|array',
            'provincia_id' => 'sometimes|array',
            'region_id' => 'sometimes|array',
            'plazas' => 'nullable|integer',
            'linea_id' => 'sometimes|integer', // ✅ Se usa línea_id
            'cuota_1' => 'nullable|numeric',
            'cuota_2' => 'nullable|numeric',
        ]);

        $territorio->update([
            'nombre_territorio' => $request->nombre_territorio ?? $territorio->nombre_territorio,
            'cod_territorio' => $request->cod_territorio ?? $territorio->cod_territorio,
            'comuna_id' => $request->has('comuna_id') ? array_map('intval', $request->comuna_id) : $territorio->comuna_id,
            'provincia_id' => $request->has('provincia_id') ? array_map('intval', $request->provincia_id) : $territorio->provincia_id,
            'region_id' => $request->has('region_id') ? array_map('intval', $request->region_id) : $territorio->region_id,
            'plazas' => $request->plazas ?? $territorio->plazas,
            'linea_id' => $request->linea_id ?? $territorio->linea_id, // ✅ Se usa línea_id
            'cuota_1' => $request->cuota_1 ?? $territorio->cuota_1,
            'cuota_2' => $request->cuota_2 ?? $territorio->cuota_2,
            'total' => ($request->cuota_1 ?? $territorio->cuota_1 ?? 0) + ($request->cuota_2 ?? $territorio->cuota_2 ?? 0),
        ]);

        // ✅ Agregar la línea de intervención asociada
        $territorio->linea = $territorio->linea_id 
            ? LineasDeIntervencion::where('id', $territorio->linea_id)->value('nombre') 
            : null;

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

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LineasDeIntervencion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LineasDeIntervencionController extends Controller
{
    /**
     * Listar todas las líneas de intervención
     * → Devuelve un array puro para el front
     */
    public function index()
    {
        try {
            $lineas = LineasDeIntervencion::all();
            return response()->json($lineas, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error en LineasDeIntervencionController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar líneas de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Crear una nueva línea de intervención
     * → Devuelve el objeto creado
     */
    public function store(Request $request)
    {
        $request->validate([
            'nombre'      => 'required|string|unique:lineasdeintervenciones,nombre',
            'descripcion' => 'nullable|string',
        ]);

        try {
            $linea = LineasDeIntervencion::create($request->only('nombre', 'descripcion'));
            return response()->json($linea, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('Error en LineasDeIntervencionController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear línea de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Mostrar una línea de intervención por ID
     * → Devuelve el objeto o 404
     */
    public function show($id)
    {
        try {
            $linea = LineasDeIntervencion::findOrFail($id);
            return response()->json($linea, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Línea no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en LineasDeIntervencionController@show id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener línea de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Actualizar una línea de intervención existente
     * → Devuelve el objeto actualizado
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nombre'      => "sometimes|required|string|unique:lineasdeintervenciones,nombre,{$id}",
            'descripcion' => 'nullable|string',
        ]);

        try {
            $linea = LineasDeIntervencion::findOrFail($id);
            $linea->update($request->only('nombre', 'descripcion'));
            return response()->json($linea, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Línea no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en LineasDeIntervencionController@update id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar línea de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Eliminar una línea de intervención
     * → Devuelve mensaje de confirmación
     */
    public function destroy($id)
    {
        try {
            $linea = LineasDeIntervencion::findOrFail($id);
            $linea->delete();
            return response()->json([
                'message' => 'Línea eliminada correctamente'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Línea no encontrada'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en LineasDeIntervencionController@destroy id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al eliminar línea de intervención'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

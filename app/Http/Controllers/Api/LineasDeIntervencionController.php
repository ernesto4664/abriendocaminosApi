<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;
use App\Models\LineasDeIntervencion;
use Illuminate\Http\Request;

class LineasDeIntervencionController extends Controller
{
    public function index()
    {
        return response()->json(LineasDeIntervencion::all());
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|unique:lineasdeintervenciones,nombre',
            'descripcion' => 'nullable|string'
        ]);

        $linea = LineasDeIntervencion::create($request->all());
        return response()->json($linea, 201);
    }

    public function update(Request $request, $id)
    {
        $linea = LineasDeIntervencion::findOrFail($id);
        $linea->update($request->all());
        return response()->json($linea);
    }

    public function show($id)
    {
        $linea = LineasDeIntervencion::find($id);

        if (!$linea) {
            return response()->json(['message' => 'Línea no encontrada'], 404);
        }

        return response()->json($linea);
    }


    public function destroy($id)
    {
        LineasDeIntervencion::destroy($id);
        return response()->json(['message' => 'Línea eliminada']);
    }
}

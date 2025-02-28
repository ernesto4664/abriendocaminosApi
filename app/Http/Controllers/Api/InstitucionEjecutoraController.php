<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InstitucionEjecutora;
use Illuminate\Http\Request;

class InstitucionEjecutoraController extends Controller {

    public function index() {
        $instituciones = InstitucionEjecutora::with('planDeIntervencion')->get();
        return response()->json($instituciones, 200);
    }
    

    public function store(Request $request) {
        $request->validate([
            'nombre_fantasia' => 'required|string|max:255',
            'nombre_legal' => 'required|string|max:255',
            'rut' => 'required|string|max:20|unique:instituciones_ejecutoras,rut',
            'telefono' => 'required|string|max:15',
            'email' => 'required|email|max:255',
            'territorio_id' => 'required|exists:territorios,id',
            'periodo_registro_desde' => 'required|date',
            'periodo_registro_hasta' => 'required|date|after_or_equal:periodo_registro_desde',
            'periodo_seguimiento_desde' => 'required|date',
            'periodo_seguimiento_hasta' => 'required|date|after_or_equal:periodo_seguimiento_desde'
        ]);

        $institucion = InstitucionEjecutora::create($request->all());
        return response()->json($institucion, 201);
    }

        public function show($id) {
            $institucion = InstitucionEjecutora::with('planDeIntervencion')->findOrFail($id);
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

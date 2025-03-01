<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Respuesta;
use Illuminate\Http\Request;

class RespuestaController extends Controller {
    public function index() {
        return response()->json(Respuesta::all(), 200);
    }

    public function store(Request $request) {
        $request->validate([
            'nna_id' => 'nullable|exists:nna,id',
            'profesional_id' => 'required|exists:users,id',
            'pregunta_id' => 'required|exists:preguntas,id',
            'respuesta' => 'required|in:cumple,no_cumple,Si,No,De acuerdo, No de acuerdo',
            'observaciones' => 'nullable|string'
        ]);

        $respuesta = Respuesta::create($request->all());
        return response()->json($respuesta, 201);
    }

    public function show($id) {
        $respuesta = Respuesta::findOrFail($id);
        return response()->json($respuesta, 200);
    }

    public function update(Request $request, $id) {
        $respuesta = Respuesta::findOrFail($id);
        $respuesta->update($request->all());

        return response()->json($respuesta, 200);
    }

    public function destroy($id) {
        Respuesta::destroy($id);
        return response()->json(['message' => 'Respuesta eliminada correctamente'], 200);
    }
}


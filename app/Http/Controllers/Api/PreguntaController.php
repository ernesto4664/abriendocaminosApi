<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pregunta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreguntaController extends Controller {
    
    public function index() {
        return response()->json(Pregunta::all(), 200);
    }

    public function store(Request $request) {
        Log::info('📌 [STORE] Recibida solicitud para crear una Pregunta', ['data' => $request->all()]);

        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id', // ✅ Verifica que la tabla se llame así en la BD
            'pregunta' => 'required|string|max:255'
        ]);

        DB::beginTransaction();

        try {
            $pregunta = Pregunta::create($request->all());

            Log::info('📝 [STORE] Pregunta creada', ['pregunta_id' => $pregunta->id]);

            DB::commit();
            return response()->json($pregunta, 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al crear la Pregunta', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo crear la pregunta', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function show($id) {
        return response()->json(Pregunta::findOrFail($id), 200);
    }

    public function update(Request $request, $id) {
        Log::info('📌 [UPDATE] Actualizando Pregunta', ['pregunta_id' => $id, 'data' => $request->all()]);

        $pregunta = Pregunta::findOrFail($id);
        DB::beginTransaction();

        try {
            $pregunta->update($request->all());

            Log::info('✅ [UPDATE] Pregunta actualizada', ['pregunta_id' => $pregunta->id]);

            DB::commit();
            return response()->json($pregunta, 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar la Pregunta', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'No se pudo actualizar la pregunta', 'detalle' => $e->getMessage()], 500);
        }
    }

    public function destroy($id) {
        Log::info('📌 [DELETE] Eliminando Pregunta', ['pregunta_id' => $id]);

        $pregunta = Pregunta::find($id);
        if (!$pregunta) {
            return response()->json(['error' => 'Pregunta no encontrada'], 404);
        }

        $pregunta->delete();
        Log::info('✅ [DELETE] Pregunta eliminada correctamente', ['pregunta_id' => $id]);

        return response()->json(['message' => 'Pregunta eliminada correctamente'], 200);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pregunta;
use App\Models\MDSFApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PreguntaController extends Controller {
    /**
     * Listar todas las preguntas
     */
    public function index() {
        $resp = new MDSFApiResponse();
        try {
            $preguntas = Pregunta::all();
            $resp->code = 200;
            $resp->data = $preguntas;
        } catch (\Exception $e) {
            Log::error("Error al listar preguntas: {$e->getMessage()}");
            $resp->code = 500;
            $resp->message = 'Error al obtener preguntas';
        }
        return $resp->json();
    }

    /**
     * Crear nueva pregunta
     */
    public function store(Request $request) {
        $resp = new MDSFApiResponse();
        Log::info('📌 [STORE] Recibida solicitud para crear una Pregunta', ['data' => $request->all()]);

        $request->validate([
            'evaluacion_id' => 'required|exists:evaluaciones,id',
            'pregunta' => 'required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $pregunta = Pregunta::create($request->all());
            Log::info('📝 [STORE] Pregunta creada', ['pregunta_id' => $pregunta->id]);

            DB::commit();
            $resp->code = 201;
            $resp->data = $pregunta;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [STORE] Error al crear la Pregunta', ['error' => $e->getMessage()]);
            $resp->code = 500;
            $resp->message = 'No se pudo crear la pregunta';
            $resp->errors = ['detalle' => $e->getMessage()];
        }
        return $resp->json();
    }

    /**
     * Mostrar pregunta específica
     */
    public function show($id) {
        $resp = new MDSFApiResponse();
        try {
            $pregunta = Pregunta::findOrFail($id);
            $resp->code = 200;
            $resp->data = $pregunta;
        } catch (\Exception $e) {
            Log::error("Error al obtener pregunta: {$e->getMessage()}");
            $resp->code = 404;
            $resp->message = 'Pregunta no encontrada';
        }
        return $resp->json();
    }

    /**
     * Actualizar pregunta existente
     */
    public function update(Request $request, $id) {
        $resp = new MDSFApiResponse();
        Log::info('📌 [UPDATE] Actualizando Pregunta', ['pregunta_id' => $id, 'data' => $request->all()]);

        $request->validate([
            'evaluacion_id' => 'sometimes|required|exists:evaluaciones,id',
            'pregunta'      => 'sometimes|required|string|max:255'
        ]);

        DB::beginTransaction();
        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->update($request->all());
            Log::info('✅ [UPDATE] Pregunta actualizada', ['pregunta_id' => $pregunta->id]);

            DB::commit();
            $resp->code = 200;
            $resp->data = $pregunta;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('❌ [UPDATE] Error al actualizar la Pregunta', ['error' => $e->getMessage()]);
            $resp->code = 500;
            $resp->message = 'No se pudo actualizar la pregunta';
            $resp->errors = ['detalle' => $e->getMessage()];
        }
        return $resp->json();
    }

    /**
     * Eliminar pregunta
     */
    public function destroy($id) {
        $resp = new MDSFApiResponse();
        Log::info('📌 [DELETE] Eliminando Pregunta', ['pregunta_id' => $id]);

        try {
            $pregunta = Pregunta::findOrFail($id);
            $pregunta->delete();
            Log::info('✅ [DELETE] Pregunta eliminada correctamente', ['pregunta_id' => $id]);

            $resp->code = 200;
            $resp->message = 'Pregunta eliminada correctamente';
        } catch (\Exception $e) {
            Log::error('❌ [DELETE] Error al eliminar la Pregunta', ['error' => $e->getMessage()]);
            $resp->code = ($e instanceof ModelNotFoundException) ? 404 : 500;
            $resp->message = ($resp->code === 404) ? 'Pregunta no encontrada' : 'Error al eliminar la pregunta';
            $resp->errors = ['detalle' => $e->getMessage()];
        }

        return $resp->json();
    }
}

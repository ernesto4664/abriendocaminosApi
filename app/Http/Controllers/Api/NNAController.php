<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;
// TODO: descomenta cuando crees el modelo NNA
// use App\Models\NNA;

class NNAController extends Controller
{
    public function index()
    {
        try {

            $nnaList = [];

            return response()->json($nnaList, Response::HTTP_OK);

        } catch (\Throwable $e) {
            Log::error('Error en NNAController@index: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al listar NNA'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {

            $nna = null; // placeholder

            return response()->json($nna, Response::HTTP_CREATED);

        } catch (\Throwable $e) {
            Log::error('Error en NNAController@store: ' . $e->getMessage());
            return response()->json([
                'message' => 'Error al crear NNA'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show($id)
    {
        try {

            $nna = null; // placeholder

            return response()->json($nna, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'NNA no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en NNAController@show id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al obtener NNA'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(Request $request, $id)
    {
        try {

            $nna = null; // placeholder

            return response()->json($nna, Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'NNA no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en NNAController@update id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al actualizar NNA'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy($id)
    {
        try {
            // TODO: NNA::destroy($id);
            return response()->json([
                'message' => 'NNA eliminado correctamente'
            ], Response::HTTP_OK);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'NNA no encontrado'
            ], Response::HTTP_NOT_FOUND);

        } catch (\Throwable $e) {
            Log::error("Error en NNAController@destroy id={$id}: " . $e->getMessage());
            return response()->json([
                'message' => 'Error al eliminar NNA'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}

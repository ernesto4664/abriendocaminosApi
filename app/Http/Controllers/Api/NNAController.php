<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MDSFApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NNAController extends Controller
{
    public function index()
    {
        $resp = new MDSFApiResponse();
        try {
            // TODO: reemplazar con tu modelo NNA::all()
            $resp->data = []; 
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('Error en NNAController@index: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al listar NNA';
        }
        return $resp->json();
    }

    public function store(Request $request)
    {
        $resp = new MDSFApiResponse();
        try {
            // TODO: validar y crear NNA
            $resp->data = null;
            $resp->code = 201;
        } catch (\Exception $e) {
            Log::error('Error en NNAController@store: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al crear NNA';
        }
        return $resp->json();
    }

    public function show($id)
    {
        $resp = new MDSFApiResponse();
        try {
            // TODO: $nna = NNA::findOrFail($id);
            $resp->data = null;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error("Error en NNAController@show id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al obtener NNA';
        }
        return $resp->json();
    }

    public function update(Request $request, $id)
    {
        $resp = new MDSFApiResponse();
        try {
            // TODO: actualizar NNA
            $resp->data = null;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error("Error en NNAController@update id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al actualizar NNA';
        }
        return $resp->json();
    }

    public function destroy($id)
    {
        $resp = new MDSFApiResponse();
        try {
            // TODO: NNA::destroy($id);
            $resp->message = 'NNA eliminado correctamente';
            $resp->code    = 200;
        } catch (\Exception $e) {
            Log::error("Error en NNAController@destroy id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al eliminar NNA';
        }
        return $resp->json();
    }
}

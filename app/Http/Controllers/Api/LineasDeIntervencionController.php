<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LineasDeIntervencion;
use App\Models\MDSFApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LineasDeIntervencionController extends Controller
{
    public function index()
    {
        $resp = new MDSFApiResponse();
        try {
            $resp->data = LineasDeIntervencion::all();
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error('Error en LineasDeIntervencionController@index: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al listar líneas de intervención';
        }
        return $resp->json();
    }

    public function store(Request $request)
    {
        $resp = new MDSFApiResponse();
        $request->validate([
            'nombre'      => 'required|string|unique:lineasdeintervenciones,nombre',
            'descripcion' => 'nullable|string',
        ]);
        try {
            $linea       = LineasDeIntervencion::create($request->all());
            $resp->data  = $linea;
            $resp->code  = 201;
        } catch (\Exception $e) {
            Log::error('Error en LineasDeIntervencionController@store: '.$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al crear línea de intervención';
        }
        return $resp->json();
    }

    public function show($id)
    {
        $resp = new MDSFApiResponse();
        try {
            $linea = LineasDeIntervencion::findOrFail($id);
            $resp->data = $linea;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error("Error en LineasDeIntervencionController@show id={$id}: ".$e->getMessage());
            $resp->code    = ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) ? 404 : 500;
            $resp->message = $resp->code === 404
                ? 'Línea no encontrada'
                : 'Error al obtener línea de intervención';
        }
        return $resp->json();
    }

    public function update(Request $request, $id)
    {
        $resp = new MDSFApiResponse();
        try {
            $linea = LineasDeIntervencion::findOrFail($id);
            $linea->update($request->all());
            $resp->data = $linea;
            $resp->code = 200;
        } catch (\Exception $e) {
            Log::error("Error en LineasDeIntervencionController@update id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al actualizar línea de intervención';
        }
        return $resp->json();
    }

    public function destroy($id)
    {
        $resp = new MDSFApiResponse();
        try {
            LineasDeIntervencion::destroy($id);
            $resp->message = 'Línea eliminada correctamente';
            $resp->code    = 200;
        } catch (\Exception $e) {
            Log::error("Error en LineasDeIntervencionController@destroy id={$id}: ".$e->getMessage());
            $resp->code    = 500;
            $resp->message = 'Error al eliminar línea de intervención';
        }
        return $resp->json();
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RespuestaNna;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GuardarRespuestasParcialesController
{
  

     public function guardarRespuestasParciales(Request $request)
{
    $data = $request->validate([
        'nna_id'         => 'required|integer',
        'evaluacion_id'  => 'required|integer',
        'respuestas'     => 'required|array',
        'respuestas.*.pregunta_id'    => 'required|integer',
        'respuestas.*.tipo'           => 'required|string',
        'respuestas.*.respuesta'      => 'nullable|string', // solo un campo!
        'respuestas.*.subpregunta_id' => 'nullable|integer',
    ]);

    foreach ($data['respuestas'] as $respuesta) {
        $subpreguntaId = $respuesta['subpregunta_id'] ?? null;
        if ($subpreguntaId === 'null' || $subpreguntaId === '') {
            $subpreguntaId = null;
        }

        \App\Models\RespuestaNna::updateOrCreate(
            [
                'nna_id'         => $data['nna_id'],
                'evaluacion_id'  => $data['evaluacion_id'],
                'pregunta_id'    => $respuesta['pregunta_id'],
                'subpregunta_id' => $subpreguntaId
            ],
            [
                'tipo'      => $respuesta['tipo'],
                'respuesta' => $respuesta['respuesta'] ?? null,
            ]
        );
    }

    return response()->json(['message' => 'Respuestas guardadas correctamente']);
}
}

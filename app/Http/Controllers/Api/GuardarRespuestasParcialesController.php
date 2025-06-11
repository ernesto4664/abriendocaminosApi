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

        Log::info('Datos recibidos en guardarRespuestasParciales:', $request->all());

                $data = $request->validate([
            'nna_id'         => 'required|exists:registro_nnas,id',
            'evaluacion_id'  => 'required|exists:evaluaciones,id',
            'respuestas'     => 'required|array',
            'respuestas.*.pregunta_id' => 'required|exists:preguntas,id',
            'respuestas.*.respuesta_opcion_id' => 'nullable|integer|min:1|exists:respuestas_opciones,id',

            'respuestas.*.tipo' => 'required|string',
            'respuestas.*.respuesta_texto' => 'nullable|string',
            'respuestas.*.subpregunta_id' => 'nullable|exists:subpreguntas,id'
        ]);
        Log::info('Datos validados:', $data);

        foreach ($data['respuestas'] as $respuesta) {
            RespuestaNna::updateOrCreate(
                [
                    'nna_id'        => $data['nna_id'],
                    'evaluacion_id' => $data['evaluacion_id'],
                    'pregunta_id'   => $respuesta['pregunta_id'],
                    'subpregunta_id'=> $respuesta['subpregunta_id'] ?? null
                ],
                [
                    'tipo'               => $respuesta['tipo'],
                    'respuesta_opcion_id'=> $respuesta['respuesta_opcion_id'] ?? null,
                    'respuesta_texto'    => $respuesta['respuesta_texto'] ?? null
                ]
            );
        }

        return response()->json(['message' => 'Respuestas guardadas parcialmente'], 200);
    }
}

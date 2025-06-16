<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistroCuidador;
use App\Models\RegistroNnas;
use Illuminate\Support\Facades\DB;
use App\Models\RespuestaNna; // Asegúrate de incluir el modelo RespuestaNna
use Illuminate\Support\Facades\Log;

class EjecucionInstrumentoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

 public function nnaConCuidadores()
{
    $nna = DB::table('registro_nnas as n')
        ->join('registro_cuidador as c', 'n.id', '=', 'c.asignar_nna')
        ->whereNotNull('c.documento_firmado')
        ->where('c.documento_firmado', '!=', '')
        ->select(
            'n.id',
            'n.rut',
            'n.nombres',
            'n.apellidos',
            'n.edad',           // ✅ existe
            'n.sexo',
            'n.nacionalidad',
            'n.profesional_id',
            'n.institucion_id',

            // 🔹 Unificamos todos los documentos firmados del/los cuidadores
            DB::raw('GROUP_CONCAT(DISTINCT c.documento_firmado SEPARATOR "; ") 
                     AS documentos_cuidadores')
        )
        // 🔹 Con este groupBy garantizamos que cada NNA salga solo una vez
        ->groupBy(
            'n.id',
            'n.rut',
            'n.nombres',
            'n.apellidos',
            'n.edad',
            'n.sexo',
            'n.nacionalidad',
            'n.profesional_id',
            'n.institucion_id'
        )
        ->get();

    return response()->json($nna);
}



    public function detalleNna($id)
    {
        // 1. Trae el NNA con profesional e institución
        $nna = DB::table('registro_nnas as n')
            ->leftJoin('usuarios_institucion as u', 'n.profesional_id', '=', 'u.id')
            ->leftJoin('instituciones_ejecutoras as i', 'n.institucion_id', '=', 'i.id')
            ->select(
                'n.*',
                'u.nombres as profesional_nombres',
                'u.apellidos as profesional_apellidos',
                'u.email as profesional_email',
                'u.rut as profesional_rut',
                'i.nombre_fantasia as institucion_nombre',
                'i.rut as institucion_rut',
                'i.email as institucion_email'
            )
            ->where('n.id', $id)
            ->first();

        if (!$nna) {
            return response()->json(['message' => 'NNA no encontrado'], 404);
        }

        // 2. Trae el cuidador asociado a este NNA
        $cuidador = DB::table('registro_cuidador')
            ->where('asignar_nna', $id)
            ->first();

        // 3. Trae el ASPL asociado a este NNA
        $aspl = DB::table('registro_aspl')
            ->where('asignar_nna', $id)
            ->first();

        // 4. Adjunta los datos al resultado
        $nna->cuidador = $cuidador;
        $nna->aspl = $aspl;

        return response()->json($nna);
    }

   public function detalleEvaluacion($id)
{
    /* ===========================
     * 1. Encabezado de la evaluación
     * =========================== */
    $evaluacion = DB::table('evaluaciones as e')
        ->leftJoin('planes_intervencion as p', 'e.plan_id', '=', 'p.id')
        ->leftJoin('lineasdeintervenciones as l', 'p.linea_id', '=', 'l.id')
        ->select(
            'e.*',
            'p.nombre   as plan_nombre',
            'p.descripcion as plan_descripcion',
            'l.nombre   as linea_nombre'
        )
        ->where('e.id', $id)
        ->first();

    if (!$evaluacion) {
        return response()->json(['message' => 'Evaluación no encontrada'], 404);
    }

    /* ===========================
     * 2. Preguntas + tipo + opciones + sub-preguntas
     * =========================== */
    $preguntas = DB::table('preguntas')
        ->where('evaluacion_id', $id)
        ->orderBy('id')
        ->get();

    foreach ($preguntas as &$pregunta) {

        /* ── Tipo de respuesta ───────────────────────── */
        $pregunta->tipo = DB::table('respuesta_tipos')
            ->where('pregunta_id', $pregunta->id)
            ->first();                     // ej. { id: 117, tipo: 'si_no' }

        /* ── Opciones “maestras” (catálogo) ────────────
         *    Nos apoyamos en la tabla  respuestas_opciones ➜ respuestas
         *    para traer SIEMPRE las opciones, aunque aún no existan
         *    respuestas reales del NNA.
         */
       $pregunta->opciones = DB::table('respuestas_opciones')
    ->join('respuestas', 'respuestas.id', '=', 'respuestas_opciones.respuesta_id')
    ->where('respuestas.pregunta_id', $pregunta->id)
    ->get();
                     // ej. [ {id:569,label:'SI'}, {id:570,label:'NO'} ]

        /* ── Sub-preguntas condicionales (si las hay) ── */
        $pregunta->subrespuestas = DB::table('respuestas_subpreguntas as rs')
            ->join('respuestas as r', 'r.id', '=', 'rs.respuesta_id')
            ->where('r.pregunta_id', $pregunta->id)
            ->orderBy('rs.id')
            ->select('rs.id', 'rs.texto', 'rs.respuesta_id')
            ->get();
    }

    /* ===========================
     * 3. Devolver estructura final
     * =========================== */
    $evaluacion->preguntas = $preguntas;

    return response()->json($evaluacion);
}


    public function evaluacionesActuales()
    {
        $year = date('Y');
        $evaluaciones = DB::table('evaluaciones')
            ->whereYear('created_at', $year)
            ->get();

        return response()->json($evaluaciones);
    }

   public function guardarRespuestas(Request $request)
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



public function estadoEvaluacionesPorNna($nna_id)
{
    // Verifica que el NNA exista
    $nnaExiste = DB::table('registro_nnas')->where('id', $nna_id)->exists();
    if (!$nnaExiste) {
        return response()->json(['message' => 'NNA no encontrado'], 404);
    }

    $evaluaciones = DB::table('evaluaciones')->get();

    $resultado = [];

    foreach ($evaluaciones as $eval) {
        $totalPreguntas = DB::table('preguntas')
            ->where('evaluacion_id', $eval->id)
            ->count();

        $respuestas = DB::table('respuestas_nna')
            ->where('nna_id', $nna_id)
            ->where('evaluacion_id', $eval->id)
            ->count();

        $estado = 'no_iniciada';
        $porcentaje = 0;

        if ($respuestas > 0 && $respuestas < $totalPreguntas) {
            $estado = 'en_proceso';
            $porcentaje = round(($respuestas / $totalPreguntas) * 100, 2);
        } elseif ($respuestas === $totalPreguntas && $totalPreguntas > 0) {
            $estado = 'completada';
            $porcentaje = 100;
        }

        $resultado[] = [
            'evaluacion_id' => $eval->id,
            'nombre' => $eval->nombre ?? null,
            'estado' => $estado,
            'total_preguntas' => $totalPreguntas,
            'respuestas' => $respuestas,
            'porcentaje' => $porcentaje
        ];
    }

    return response()->json([
        'nna_id' => $nna_id,
        'evaluaciones' => $resultado
    ]);
}

public function respuestasPorNnaYEvaluacion($nnaId, $evaluacionId)
{
    $respuestas = DB::table('respuestas_nna')
        ->where('nna_id', $nnaId)
        ->where('evaluacion_id', $evaluacionId)
        ->select('pregunta_id', 'respuesta', 'subpregunta_id', 'tipo')
        ->get();

    return response()->json($respuestas);
}



}

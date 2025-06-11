<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RegistroCuidador;
use App\Models\RegistroNnas;
use Illuminate\Support\Facades\DB;

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
}

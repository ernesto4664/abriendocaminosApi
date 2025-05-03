<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetallePonderacion extends Model
{
    protected $table = 'detalle_ponderaciones';

    protected $fillable = [
        'ponderacion_id',
        'pregunta_id',
        'subpregunta_id',
        'tipo',
        'valor',
        'respuesta_correcta',
        'respuesta_correcta_id',
    ];

    public $timestamps = true;

    public function ponderacion(): BelongsTo
    {
        return $this->belongsTo(Ponderacion::class, 'ponderacion_id', 'id');
    }

    public function pregunta(): BelongsTo
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    public function subpregunta(): BelongsTo
    {
        // apunta a tu modelo RespuestaSubpregunta
        return $this->belongsTo(RespuestaSubpregunta::class, 'subpregunta_id');
    }

    /**
     * Opción “normal” (si_no, emojis, personalizada...)
     */
    public function respuestaOpcionCorrecta(): BelongsTo
    {
        return $this->belongsTo(RespuestaOpcion::class, 'respuesta_correcta_id');
    }

    /**
     * Opción Likert (para subpreguntas likert)
     */
    public function opcionLikertCorrecta(): BelongsTo
    {
        return $this->belongsTo(OpcionLikert::class, 'respuesta_correcta_id');
    }
}

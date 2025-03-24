<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model
{
    use HasFactory;

    protected $table = 'respuestas';

    protected $fillable = [
        'evaluacion_id',  // Agregado para asegurarnos de que se guarda correctamente
        'nna_id', 
        'profesional_id', 
        'pregunta_id', 
        'respuesta', 
        'observaciones', 
    ];

    // Cargar relaciones por defecto (Opcional, mejora rendimiento)
    protected $with = ['opciones', 'subpreguntas.opcionesLikert'];

    /**
     * Relación con Evaluación
     */
    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }

    /**
     * Relación con el niño/a asociado (NNA)
     */
    public function nna()
    {
        return $this->belongsTo(Nna::class, 'nna_id');
    }

    /**
     * Relación con el profesional que registró la respuesta
     */
    public function profesional()
    {
        return $this->belongsTo(User::class, 'profesional_id');
    }

    /**
     * Relación con la pregunta correspondiente
     */
    public function pregunta()
    {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }

    /**
     * Relación con las opciones de la respuesta
     */
    public function opciones()
    {
        return $this->hasMany(RespuestaOpcion::class, 'respuesta_id');
    }
    
    /**
     * Relación con las opciones de tipo Likert
     */
    public function opcionesLikert()
    {
        return $this->hasMany(OpcionLikert::class, 'respuesta_id');
    }

    /**
     * Relación con las opciones de la Barra de Satisfacción (0-10)
     */
    public function opcionesBarraSatisfaccion()
    {
        return $this->hasMany(OpcionBarraSatisfaccion::class, 'respuesta_id');
    }
    
    /**
     * Relación con las subpreguntas (para Likert u otras preguntas anidadas)
     */
    public function subpreguntas()
    {
        return $this->hasMany(RespuestaSubpregunta::class, 'respuesta_id');
    }
}

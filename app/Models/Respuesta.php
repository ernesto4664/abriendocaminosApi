<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model {
    use HasFactory;

    protected $table = 'respuestas';

    protected $fillable = [
        'nna_id', 
        'profesional_id', 
        'pregunta_id', 
        'respuesta', 
        'observaciones', 
        'tipo'
    ];

    public function pregunta() {
        return $this->belongsTo(Pregunta::class, 'pregunta_id')->withDefault();
    }

    public function opciones() {
        return $this->hasMany(RespuestaOpcion::class, 'respuesta_id');
    }

    public function subpreguntas() {
        return $this->hasMany(RespuestaSubpregunta::class, 'respuesta_id');
    }

    public function opcionesLikert() {
        return $this->hasMany(OpcionLikert::class, 'respuesta_id');
    }

    public function barraSatisfaccionOpciones() {
        return $this->hasMany(OpcionBarraSatisfaccion::class, 'respuesta_id');
    }
}




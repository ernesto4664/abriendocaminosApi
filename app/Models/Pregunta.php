<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pregunta extends Model {
    use HasFactory;

    protected $table = 'preguntas';

    protected $fillable = ['evaluacion_id', 'pregunta'];

    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }
    
    public function tiposDeRespuesta() {
        return $this->hasMany(RespuestaTipo::class, 'pregunta_id');
    }
    
    public function respuestas() {
        return $this->hasMany(Respuesta::class, 'pregunta_id');
    }

    public function subpreguntas()
    {
        return $this->hasMany(RespuestaSubpregunta::class, 'pregunta_id');
    }
}


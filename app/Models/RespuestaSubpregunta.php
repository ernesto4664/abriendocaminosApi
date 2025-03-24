<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaSubpregunta extends Model {
    use HasFactory;

    protected $table = 'respuestas_subpreguntas';

    protected $fillable = ['respuesta_id', 'texto'];

    public function respuesta() {
        return $this->belongsTo(Respuesta::class, 'respuesta_id');
    }

    // ✅ Agregar la relación correcta con `OpcionLikert`
    public function opcionesLikert() {
        return $this->hasMany(OpcionLikert::class, 'subpregunta_id');
    }

    public function opciones()
    {
        return $this->hasMany(RespuestaOpcion::class, 'respuesta_id');
    }
    
}


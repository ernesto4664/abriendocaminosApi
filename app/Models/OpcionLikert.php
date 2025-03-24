<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionLikert extends Model {
    use HasFactory;

    protected $table = 'opciones_likert';

    protected $fillable = ['subpregunta_id', 'label', 'respuesta_id']; // Agregar 'respuesta_id' a $fillable

    // ✅ Relación con Respuesta
    public function respuesta()
    {
        return $this->belongsTo(Respuesta::class);
    }

    // ✅ Relación con `RespuestaSubpregunta`
    public function subpregunta() {
        return $this->belongsTo(RespuestaSubpregunta::class, 'subpregunta_id');
    }
}




<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Respuesta extends Model {
    use HasFactory;

    protected $table = 'respuestas';

    protected $fillable = ['nna_id', 'profesional_id', 'pregunta_id', 'respuesta', 'observaciones'];

    public function nna() {
        return $this->belongsTo(NNA::class, 'nna_id');
    }

    public function pregunta() {
        return $this->belongsTo(Pregunta::class, 'pregunta_id');
    }
}


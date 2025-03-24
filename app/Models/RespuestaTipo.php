<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaTipo extends Model {
    use HasFactory;

    protected $table = 'respuesta_tipos';
    protected $fillable = ['pregunta_id', 'tipo'];

    public function pregunta() {
        return $this->belongsTo(Pregunta::class);
    }
}

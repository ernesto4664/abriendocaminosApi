<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RespuestaOpcion extends Model
{
    use HasFactory;

    protected $table = 'respuestas_opciones';

    protected $fillable = ['respuesta_id', 'label', 'valor'];

    // Relación inversa a Respuesta
    public function respuesta()
    {
        return $this->belongsTo(Respuesta::class, 'respuesta_id');
    }
}

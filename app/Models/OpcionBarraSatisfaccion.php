<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OpcionBarraSatisfaccion extends Model {
    use HasFactory;

    protected $table = 'opciones_barra_satisfaccion';

    protected $fillable = ['respuesta_id', 'valor'];

    public function respuesta() {
        return $this->belongsTo(Respuesta::class, 'respuesta_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\RegistroNnas;

class RespuestaNna extends Model
{
    protected $table = 'respuestas_nna';

   protected $fillable = [
    'nna_id',
    'evaluacion_id',
    'pregunta_id',
    'subpregunta_id',
    'tipo',
    'respuesta',
];


    /**
     * Relación con el modelo RegistroNna
     */
    public function nna()
    {
        return $this->belongsTo(RegistroNnas::class, 'nna_id');
    }
}

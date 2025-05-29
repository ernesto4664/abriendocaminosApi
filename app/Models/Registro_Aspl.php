<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Registro_Aspl extends Model
{
    protected $table = 'registro_aspl';
    protected $fillable = [
        'rut_ppl',
        'dv_ppl',
        'asignar_nna',
        'nombres_ppl',
        'apellidos_ppl',
        'sexo_ppl',
        'centro_penal',
        'region_penal',
        'nacionalidad_ppl',
        'participa_programa',
        'motivo_no_participa',
    ];
}

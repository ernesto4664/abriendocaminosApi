<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RegistroNnas extends Model
{
    protected $table = 'registro_nnas'; // o el nombre real de tu tabla    
   protected $fillable = [
        'profesional_id',
        'institucion_id',
        'rut',
        'dv',
        'nombres',
        'apellidos',
        'edad',
        'sexo',
        'vias_ingreso',
        'parentesco_aspl',
        'parentesco_cp',
        'nacionalidad',
        'participa_programa',
        'motivo_no_participa',
        'documento_firmado',
    ];

    // Relación con el profesional
    public function profesional()
    {
        return $this->belongsTo(UsuarioInstitucion::class, 'profesional_id');
    }

    // Relación con la institución
    public function institucion()
    {
        return $this->belongsTo(InstitucionEjecutora::class, 'institucion_id');
    }
}
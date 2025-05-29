<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RegistroCuidador extends Model
{
    use HasFactory;

    protected $table = 'registro_cuidador';

    protected $fillable = [
        'rut',
        'dv',
        'nombres',
        'apellidos',
        'asignar_nna',
        'sexo',
        'edad',
        'parentesco_aspl',
        'parentesco_nna',
        'nacionalidad',
        'participa_programa',
        'motivo_no_participa',
        'documento_firmado',
    ];

    // Si tienes la tabla de NNA y quieres definir relación:
    public function nna()
    {
        return $this->belongsTo(Nna::class, 'asignar_nna');
    }
}

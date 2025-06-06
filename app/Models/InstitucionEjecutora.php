<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitucionEjecutora extends Model {
    use HasFactory;

    protected $table = 'instituciones_ejecutoras';

    protected $fillable = [
    'nombre_fantasia',
    'nombre_legal',
    'rut',
    'telefono',
    'email',
    'planesdeintervencion_id',
    'territorio_id',
    'plazas',
    'periodo_registro_desde',
    'periodo_registro_hasta',
    'periodo_seguimiento_desde',
    'periodo_seguimiento_hasta',
    ];

    public function territorio() {
        return $this->belongsTo(Territorio::class, 'territorio_id');
    }

    public function nna() {
        return $this->hasMany(NNA::class, 'institucion_id');
    }

    public function planDeIntervencion() {
        return $this->belongsTo(PlanIntervencion::class, 'planesdeintervencion_id');
    }
    
}


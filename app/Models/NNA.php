<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NNA extends Model {
    use HasFactory;

    protected $table = 'nna';

    protected $fillable = ['rut', 'nombres', 'apellidos', 'edad', 'sexo', 'institucion_id'];

    public function institucionEjecutora() {
        return $this->belongsTo(InstitucionEjecutora::class, 'institucion_id');
    }

    public function respuestas() {
        return $this->hasMany(Respuesta::class, 'nna_id');
    }
}


<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Evaluacion extends Model {
    use HasFactory;

    protected $table = 'evaluaciones';

    protected $fillable = ['plan_id', 'nombre'];

    public function planIntervencion() {
        return $this->belongsTo(PlanIntervencion::class, 'plan_id');
    }

    public function preguntas() {
        return $this->hasMany(Pregunta::class, 'evaluacion_id');
    }
}


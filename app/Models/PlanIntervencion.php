<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanIntervencion extends Model {
    use HasFactory;

    protected $table = 'planes_intervencion';

    protected $fillable = ['nombre', 'descripcion', 'linea'];

    public function evaluaciones() {
        return $this->hasMany(Evaluacion::class, 'plan_id');
    }
}


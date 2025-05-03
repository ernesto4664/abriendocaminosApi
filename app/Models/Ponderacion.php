<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ponderacion extends Model
{
    // Le decimos explícitamente el nombre de la tabla (si no siguiera convención)
    protected $table = 'ponderaciones';

    // Columnas asignables masivamente
    protected $fillable = [
        'plan_id',
        'evaluacion_id',
        'user_id',
    ];

    /**
     * Relación 1-a-N con DetallePonderacion
     */
    public function evaluacion()
    {
        return $this->belongsTo(Evaluacion::class, 'evaluacion_id');
    }

    public function detalles()
    {
        return $this->hasMany(DetallePonderacion::class);
    }
}
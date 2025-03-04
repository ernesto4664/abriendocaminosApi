<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Territorio extends Model
{
    use HasFactory;

    protected $table = 'territorios';

    protected $fillable = [
        'nombre_territorio', 'region_id', 'provincia_id', 'cod_territorio',
        'comuna_id', 'plazas', 'linea_id', 'cuota_1', 'cuota_2', 'total'
    ];

    // Casts para convertir los JSON a arrays automáticamente
    protected $casts = [
        'region_id' => 'array',
        'provincia_id' => 'array',
        'comuna_id' => 'array'
    ];

    // Relación con las provincias
    public function getProvinciasAttribute()
    {
        $provinciaIds = is_array($this->provincia_id) ? $this->provincia_id : json_decode($this->provincia_id, true) ?? [];
        return Provincia::whereIn('id', $provinciaIds)->get(['id', 'nombre']);
    }

    // Relación con las comunas
    public function getComunasAttribute()
    {
        $comunaIds = is_array($this->comuna_id) ? $this->comuna_id : json_decode($this->comuna_id, true) ?? [];
        return Comuna::whereIn('id', $comunaIds)->get(['id', 'nombre']);
    }

    // Relación con las regiones
    public function getRegionesAttribute()
    {
        $regionIds = is_array($this->region_id) ? $this->region_id : json_decode($this->region_id, true) ?? [];
        return Region::whereIn('id', $regionIds)->get(['id', 'nombre']);
    }

    // ✅ Relación con la línea de intervención
    public function linea()
    {
        return $this->belongsTo(LineasDeIntervencion::class, 'linea_id');
    }
}

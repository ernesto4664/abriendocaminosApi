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

    public function comunas()
    {
        return $this->belongsToMany(Comuna::class, 'comuna_id', 'id');
    }

    public function provincias()
    {
        return $this->belongsToMany(Provincia::class, 'provincia_id', 'id');
    }

    public function regiones()
    {
        return $this->belongsToMany(Region::class, 'region_id', 'id');
    }

    public function linea()
    {
        return $this->belongsTo(LineasDeIntervencion::class, 'linea_id');
    }
}

<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LineasDeIntervencion extends Model
{
    protected $table = 'lineasdeintervenciones';

    protected $fillable = ['nombre', 'descripcion'];

    public function planDeIntervencion()
    {
        return $this->hasOne(\App\Models\PlanIntervencion::class, 'linea_id');
    }

}

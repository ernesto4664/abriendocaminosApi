<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class UsuariosInstitucion extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $table = 'usuarios_institucion'; // Nombre exacto de la tabla

    protected $fillable = [
        'nombres',
        'apellidos',
        'rut',
        'sexo',
        'fecha_nacimiento',
        'profesion',
        'email',
        'rol',
        'region_id',
        'provincia_id',
        'comuna_id',
        'institucion_id',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'password' => 'hashed',
    ];

    /**
     * 🔹 Relación con la tabla de regiones
     */
    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id');
    }

    /**
     * 🔹 Relación con la tabla de provincias
     */
    public function provincia()
    {
        return $this->belongsTo(Provincia::class, 'provincia_id');
    }

    /**
     * 🔹 Relación con la tabla de comunas
     */
    public function comuna()
    {
        return $this->belongsTo(Comuna::class, 'comuna_id');
    }

    /**
     * 🔹 Relación con la tabla de instituciones ejecutoras
     */
    public function institucion()
    {
        return $this->belongsTo(InstitucionEjecutora::class, 'institucion_id');
    }
}

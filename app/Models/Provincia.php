<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provincia extends Model
{
    use HasFactory;

    protected $table = 'provincias';
    public $timestamps = false;

    public function region()
    {
        return $this->belongsTo(Region::class, 'region_id', 'id');
    }

    public function comunas()
    {
        return $this->hasMany(Comuna::class, 'provincia_id', 'id');
    }
}

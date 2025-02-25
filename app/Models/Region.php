<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Region extends Model
{
    use HasFactory;

    protected $table = 'regions';
    public $timestamps = false;

    public function provincias()
    {
        return $this->hasMany(Provincia::class, 'region_id', 'id');
    }
}

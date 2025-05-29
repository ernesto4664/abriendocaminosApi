<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Documento_Formularios extends Model
{
    //
    protected $table = 'documentos_formularios'; // o el nombre real de tu tabla

    protected $fillable = ['nombre','formulario_destino','ruta_archivo'];

}

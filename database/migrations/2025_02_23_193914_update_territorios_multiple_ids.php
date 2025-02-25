<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up()
    {
        Schema::table('territorios', function (Blueprint $table) {
            // Obtener columnas existentes en la tabla
            $columnas = DB::select("SHOW COLUMNS FROM territorios");

            // Convertir a un array de nombres de columnas
            $columnasExistentes = array_map(fn($col) => $col->Field, $columnas);

            // Si las columnas no existen, agrégalas
            if (!in_array('comuna_id', $columnasExistentes)) {
                $table->text('comuna_id')->nullable();
            }
            if (!in_array('provincia_id', $columnasExistentes)) {
                $table->text('provincia_id')->nullable();
            }
            if (!in_array('region_id', $columnasExistentes)) {
                $table->text('region_id')->nullable();
            }

            // Cambiar el tipo de las columnas si existen
            if (in_array('comuna_id', $columnasExistentes)) {
                $table->text('comuna_id')->change();
            }
            if (in_array('provincia_id', $columnasExistentes)) {
                $table->text('provincia_id')->change();
            }
            if (in_array('region_id', $columnasExistentes)) {
                $table->text('region_id')->change();
            }
        });
    }

    public function down()
    {
        Schema::table('territorios', function (Blueprint $table) {
            // Restaurar los campos a INTEGER solo si existen
            $columnas = DB::select("SHOW COLUMNS FROM territorios");
            $columnasExistentes = array_map(fn($col) => $col->Field, $columnas);

            if (in_array('comuna_id', $columnasExistentes)) {
                $table->integer('comuna_id')->change();
            }
            if (in_array('provincia_id', $columnasExistentes)) {
                $table->integer('provincia_id')->change();
            }
            if (in_array('region_id', $columnasExistentes)) {
                $table->integer('region_id')->change();
            }
        });
    }
};




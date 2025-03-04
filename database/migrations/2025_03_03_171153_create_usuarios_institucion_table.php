<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('usuarios_institucion', function (Blueprint $table) {
            $table->id();
            $table->string('nombres');
            $table->string('apellidos');
            $table->string('rut')->unique();
            $table->enum('sexo', ['M', 'F']);
            $table->date('fecha_nacimiento');
            $table->text('profesion');
            $table->string('email')->unique();
            $table->enum('rol', ['SEREMI', 'COORDINADOR', 'PROFESIONAL'])->default('PROFESIONAL');
            $table->unsignedInteger('region_id');  // Cambiado a unsignedInteger para coincidir con la tabla regions
            $table->unsignedInteger('provincia_id'); // Cambiado a unsignedInteger para coincidir con provincias
            $table->unsignedBigInteger('comuna_id'); // En comunas usa bigint(20), por eso lo dejamos así
            $table->unsignedBigInteger('institucion_id'); // Para instituciones_ejecutoras

            $table->string('password');
            $table->timestamps();

            // Claves foráneas
            $table->foreign('region_id')->references('id')->on('regions')->onDelete('cascade');
            $table->foreign('provincia_id')->references('id')->on('provincias')->onDelete('cascade');
            $table->foreign('comuna_id')->references('id')->on('comunas')->onDelete('cascade');
            $table->foreign('institucion_id')->references('id')->on('instituciones_ejecutoras')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuarios_institucion');
    }
};



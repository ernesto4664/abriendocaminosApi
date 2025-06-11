<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioProvinciaTable extends Migration
{
    public function up()
    {
        Schema::create('usuario_provincia', function (Blueprint $table) {
            // Coincide con usuarios_institucion.id (bigIncrements)
            $table->unsignedBigInteger('usuario_id');
            // Coincide con provincias.id (increments → INT UNSIGNED)
            $table->unsignedInteger('provincia_id');

            $table->foreign('usuario_id')
                  ->references('id')->on('usuarios_institucion')
                  ->onDelete('cascade');
            $table->foreign('provincia_id')
                  ->references('id')->on('provincias')
                  ->onDelete('cascade');

            $table->primary(['usuario_id', 'provincia_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuario_provincia');
    }
}

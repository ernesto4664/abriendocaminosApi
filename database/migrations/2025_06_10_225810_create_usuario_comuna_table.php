<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioComunaTable extends Migration
{
    public function up()
    {
        Schema::create('usuario_comuna', function (Blueprint $table) {
            // Coincide con usuarios_institucion.id (bigIncrements)
            $table->unsignedBigInteger('usuario_id');
            // Coincide con comunas.id (bigIncrements → BIGINT UNSIGNED)
            $table->unsignedBigInteger('comuna_id');

            $table->foreign('usuario_id')
                  ->references('id')->on('usuarios_institucion')
                  ->onDelete('cascade');
            $table->foreign('comuna_id')
                  ->references('id')->on('comunas')
                  ->onDelete('cascade');

            $table->primary(['usuario_id', 'comuna_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuario_comuna');
    }
}

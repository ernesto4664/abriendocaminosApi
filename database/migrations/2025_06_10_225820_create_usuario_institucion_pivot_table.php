<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUsuarioInstitucionPivotTable extends Migration
{
    public function up()
    {
        Schema::create('usuario_institucion_pivot', function (Blueprint $table) {
            // Coincide con usuarios_institucion.id (bigIncrements)
            $table->unsignedBigInteger('usuario_id');
            // Debe coincidir con instituciones_ejecutoras.id (bigIncrements → BIGINT UNSIGNED)
            $table->unsignedBigInteger('institucion_id');

            $table->foreign('usuario_id')
                  ->references('id')->on('usuarios_institucion')
                  ->onDelete('cascade');
            $table->foreign('institucion_id')
                  ->references('id')->on('instituciones_ejecutoras')
                  ->onDelete('cascade');

            $table->primary(['usuario_id', 'institucion_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('usuario_institucion_pivot');
    }
}

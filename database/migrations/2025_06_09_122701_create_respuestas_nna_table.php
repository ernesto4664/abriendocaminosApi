<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRespuestasNnaTable extends Migration
{
    public function up()
    {
       Schema::create('respuestas_nna', function (Blueprint $table) {
    $table->id();
    $table->unsignedBigInteger('nna_id');
    $table->unsignedBigInteger('evaluacion_id');
    $table->unsignedBigInteger('pregunta_id');
    $table->unsignedBigInteger('subpregunta_id')->nullable();
    $table->string('tipo');
    $table->unsignedBigInteger('respuesta_opcion_id')->nullable();
    $table->text('respuesta_texto')->nullable();
    $table->timestamps();

    $table->foreign('nna_id')->references('id')->on('registro_nnas')->onDelete('cascade');
    $table->foreign('evaluacion_id')->references('id')->on('evaluaciones')->onDelete('cascade');
    $table->foreign('pregunta_id')->references('id')->on('preguntas')->onDelete('cascade');
    $table->foreign('subpregunta_id')->references('id')->on('respuestas_subpreguntas')->onDelete('set null');
    $table->foreign('respuesta_opcion_id')->references('id')->on('respuestas_opciones')->onDelete('set null');
});
    }

    public function down()
    {
        Schema::dropIfExists('respuestas_nna');
    }
}

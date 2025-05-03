<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('detalle_ponderaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ponderacion_id');
            $table->unsignedBigInteger('pregunta_id');

            // Para Likert: apuntamos a tu tabla respuestas_subpreguntas
            $table->unsignedBigInteger('subpregunta_id')->nullable();

            $table->string('tipo');
            $table->text('respuesta_correcta')->nullable();

            // Para los tipos con opción predefinida: apuntamos a respuestas_opciones
            $table->unsignedBigInteger('respuesta_correcta_id')->nullable();

            $table->decimal('valor', 5, 2);
            $table->timestamps();

            // Constraints
            $table->foreign('ponderacion_id')
                  ->references('id')
                  ->on('ponderaciones')
                  ->onDelete('cascade');

            $table->foreign('pregunta_id')
                  ->references('id')
                  ->on('preguntas')
                  ->onDelete('cascade');

            $table->foreign('subpregunta_id')
                  ->references('id')
                  ->on('respuestas_subpreguntas')
                  ->nullOnDelete();

            $table->foreign('respuesta_correcta_id')
                  ->references('id')
                  ->on('respuestas_opciones')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('detalle_ponderaciones');
    }
};
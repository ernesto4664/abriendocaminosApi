<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // 1) soltar la FK antigua
            $table->dropForeign(['respuesta_correcta_id']);

            // 2) volver a definirla apuntando a la tabla respuestas_opciones
            $table
                ->foreign('respuesta_correcta_id')
                ->references('id')
                ->on('respuestas_opciones')
                ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // revertir al estado anterior apuntando a 'respuestas'
            $table->dropForeign(['respuesta_correcta_id']);
            $table
                ->foreign('respuesta_correcta_id')
                ->references('id')
                ->on('respuestas')
                ->nullOnDelete();
        });
    }
};

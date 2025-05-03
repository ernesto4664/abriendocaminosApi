<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropFkRespuestaCorrectaIdFromDetallePonderaciones extends Migration
{
    public function up()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // Este es el nombre de tu constraint actual; ajusta si difiere
            $table->dropForeign('detalle_ponderaciones_respuesta_correcta_id_foreign');
            // (Opcional) Si lo deseas, podrías marcar la columna nullable o cambiar tipo
            // $table->integer('respuesta_correcta_id')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // Vuelve a crear la FK hacia respuestas_opciones
            $table->foreign('respuesta_correcta_id')
                  ->references('id')
                  ->on('respuestas_opciones')
                  ->onDelete('cascade');
        });
    }
}
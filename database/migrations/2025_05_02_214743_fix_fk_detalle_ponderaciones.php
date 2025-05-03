<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FixFkDetallePonderaciones extends Migration
{
    public function up()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // 1) eliminar la restricción vieja (ajusta el nombre si difiere en tu BBDD)
            $table->dropForeign('detalle_ponderaciones_respuesta_correcta_id_foreign');

            // 2) crear la nueva FK: siempre referenciará respuestas_opciones.id
            $table
              ->foreign('respuesta_correcta_id')
              ->references('id')
              ->on('respuestas_opciones')
              ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            $table->dropForeign(['respuesta_correcta_id']);
            // Si quieres restaurar la FK original sobre opciones_likert, la puedes volver a definir aquí.
        });
    }
}
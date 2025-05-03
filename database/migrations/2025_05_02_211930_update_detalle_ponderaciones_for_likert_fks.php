<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateDetallePonderacionesForLikertFks extends Migration
{
    public function up()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // 1) Añadir columna subpregunta_id si aún no existe:
            if (! Schema::hasColumn('detalle_ponderaciones', 'subpregunta_id')) {
                $table->unsignedBigInteger('subpregunta_id')->nullable()->after('respuesta_correcta_id');
            }

            // 2) Quitar FK antigua sobre respuestas_opciones
            $table->dropForeign(['respuesta_correcta_id']);

            // 3) Añadir FK para que apunte a opciones_likert.id
            $table->foreign('respuesta_correcta_id')
                  ->references('id')
                  ->on('opciones_likert')
                  ->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::table('detalle_ponderaciones', function (Blueprint $table) {
            // Revertir cambios

            $table->dropForeign(['respuesta_correcta_id']);

            $table->foreign('respuesta_correcta_id')
                  ->references('id')
                  ->on('respuestas_opciones')
                  ->onDelete('cascade');

            $table->dropColumn('subpregunta_id');
        });
    }
}

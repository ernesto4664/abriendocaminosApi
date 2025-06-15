<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RenameColumnsRespuestasNna extends Migration
{
    public function up()
    {
        Schema::table('respuestas_nna', function (Blueprint $table) {
            // Añade la nueva columna
            $table->longText('respuesta')->nullable()->after('tipo');

            // Elimina la foreign key si existe
            if (Schema::hasColumn('respuestas_nna', 'respuesta_opcion_id')) {
                $table->dropForeign(['respuesta_opcion_id']); // eliminar restricción
                $table->dropColumn('respuesta_opcion_id');     // eliminar la columna
            }

            if (Schema::hasColumn('respuestas_nna', 'respuesta_texto')) {
                $table->dropColumn('respuesta_texto');
            }
        });
    }

    public function down()
    {
        Schema::table('respuestas_nna', function (Blueprint $table) {
            $table->integer('respuesta_opcion_id')->unsigned()->nullable()->after('tipo');
            $table->foreign('respuesta_opcion_id')->references('id')->on('respuestas_opciones');
            
            $table->text('respuesta_texto')->nullable()->after('respuesta_opcion_id');
            $table->dropColumn('respuesta');
        });
    }
}

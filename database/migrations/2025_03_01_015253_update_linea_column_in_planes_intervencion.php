<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('planes_intervencion', function (Blueprint $table) {
            // 🔥 Asegurar que primero se renombra la columna
            if (Schema::hasColumn('planes_intervencion', 'linea')) {
                $table->renameColumn('linea', 'linea_id');
            }

            // 🔥 Modificar la columna para que sea unsigned BigInt
            $table->unsignedBigInteger('linea_id')->change();

            // 🔥 Agregar la clave foránea a lineasdeintervenciones
            $table->foreign('linea_id')
                ->references('id')
                ->on('lineasdeintervenciones')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('planes_intervencion', function (Blueprint $table) {
            // 🔥 Eliminar la clave foránea
            $table->dropForeign(['linea_id']);

            // 🔥 Restaurar el nombre de la columna
            if (Schema::hasColumn('planes_intervencion', 'linea_id')) {
                $table->renameColumn('linea_id', 'linea');
            }
        });
    }
};



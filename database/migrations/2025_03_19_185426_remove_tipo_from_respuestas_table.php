<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            if (Schema::hasColumn('respuestas', 'tipo')) {
                $table->dropColumn('tipo'); // 🔥 Eliminar el campo `tipo`
            }
        });
    }

    public function down()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->enum('tipo', [
                'texto', 'barra_satisfaccion', '5emojis', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero', 'opcion', 'opcion_personalizada'
            ])->nullable(); // 🔙 Volver a agregar `tipo` si se revierte la migración
        });
    }
};

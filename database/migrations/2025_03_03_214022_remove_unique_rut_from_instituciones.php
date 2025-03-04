<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('instituciones_ejecutoras', function (Blueprint $table) {
            $table->dropUnique('instituciones_ejecutoras_rut_unique'); // Eliminar restricción unique
        });
    }

    public function down() {
        Schema::table('instituciones_ejecutoras', function (Blueprint $table) {
            $table->unique('rut'); // Volver a agregar unique en caso de rollback
        });
    }
};


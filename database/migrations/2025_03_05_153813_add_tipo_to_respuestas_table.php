<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->enum('tipo', ['texto', 'barra_satisfaccion'])->default('texto')->after('observaciones');
        });
    }

    public function down() {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->dropColumn('tipo');
        });
    }
};

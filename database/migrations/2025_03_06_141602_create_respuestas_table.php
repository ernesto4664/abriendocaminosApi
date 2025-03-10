<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::table('respuestas', function (Blueprint $table) {
            if (!Schema::hasColumn('respuestas', 'nna_id')) {
                $table->foreignId('nna_id')->nullable()->constrained('nnas')->onDelete('cascade');
            }
            if (!Schema::hasColumn('respuestas', 'profesional_id')) {
                $table->foreignId('profesional_id')->nullable()->constrained('profesionales')->onDelete('cascade');
            }
            if (!Schema::hasColumn('respuestas', 'tipo')) {
                $table->enum('tipo', ['texto', 'barra_satisfaccion', 'si_no', 'si_no_noestoyseguro', 'likert', 'numero'])->default('texto');
            }
            if (!Schema::hasColumn('respuestas', 'observaciones')) {
                $table->text('observaciones')->nullable();
            }
        });
    }

    public function down() {
        Schema::table('respuestas', function (Blueprint $table) {
            if (Schema::hasColumn('respuestas', 'nna_id')) {
                $table->dropForeign(['nna_id']);
                $table->dropColumn('nna_id');
            }
            if (Schema::hasColumn('respuestas', 'profesional_id')) {
                $table->dropForeign(['profesional_id']);
                $table->dropColumn('profesional_id');
            }
            if (Schema::hasColumn('respuestas', 'tipo')) {
                $table->dropColumn('tipo');
            }
            if (Schema::hasColumn('respuestas', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });
    }
};

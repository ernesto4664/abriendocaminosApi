<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->enum('tipo', [
                'texto', 
                'barra_satisfaccion', 
                'si_no', 
                'si_no_noestoyseguro', 
                '5emojis', 
                'likert', 
                'numero', 
                'opcion', 
                'opcion_personalizada' // 🔹 Se agrega el nuevo tipo
            ])->change();
        });
    }

    public function down()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->enum('tipo', [
                'texto', 
                'barra_satisfaccion', 
                'si_no', 
                'si_no_noestoyseguro', 
                '5emojis', 
                'likert', 
                'numero', 
                'opcion'
            ])->change();
        });
    }
};

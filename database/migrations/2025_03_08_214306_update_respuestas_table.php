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
            $table->bigInteger('profesional_id')->unsigned()->nullable()->change();
            $table->enum('respuesta', ['cumple', 'no_cumple'])->nullable()->change();
            $table->enum('tipo', [
                'texto', 
                'barra_satisfaccion', 
                '5emojis', 
                'si_no', 
                'si_no_noestoyseguro', 
                'likert', 
                'numero'
            ])->default('texto')->change();
        });
    }
    
    public function down()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->bigInteger('profesional_id')->unsigned()->change();
            $table->enum('respuesta', ['cumple', 'no_cumple'])->change();
            $table->enum('tipo', ['texto', 'barra_satisfaccion'])->default('texto')->change();
        });
    }
    
};

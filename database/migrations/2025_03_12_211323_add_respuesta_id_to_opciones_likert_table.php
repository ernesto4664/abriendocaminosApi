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
        Schema::table('opciones_likert', function (Blueprint $table) {
            $table->unsignedBigInteger('respuesta_id'); // Agregar la columna
            $table->foreign('respuesta_id')->references('id')->on('respuestas')->onDelete('cascade'); // Establecer la relación con la tabla respuestas
        });
    }
    
    public function down()
    {
        Schema::table('opciones_likert', function (Blueprint $table) {
            $table->dropForeign(['respuesta_id']);
            $table->dropColumn('respuesta_id');
        });
    }
    
};

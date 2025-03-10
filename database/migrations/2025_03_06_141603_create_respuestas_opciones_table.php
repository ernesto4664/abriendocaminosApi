<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('respuestas_opciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('respuesta_id')->constrained('respuestas')->onDelete('cascade');
            $table->string('label'); // Texto de la opción
            $table->string('valor')->nullable(); // Valor seleccionado
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('respuestas_opciones');
    }
};

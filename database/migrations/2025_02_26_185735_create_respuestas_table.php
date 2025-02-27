<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nna_id')->nullable()->constrained('nna')->onDelete('set null');
            $table->foreignId('profesional_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('pregunta_id')->constrained('preguntas')->onDelete('cascade');
            $table->enum('respuesta', ['cumple', 'no_cumple']);
            $table->text('observaciones')->nullable();
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('respuestas');
    }
};


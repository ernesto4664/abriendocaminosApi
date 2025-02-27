<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('planes_intervencion', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->enum('linea', ['1', '2']); // Línea 1 o 2
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('planes_intervencion');
    }
};


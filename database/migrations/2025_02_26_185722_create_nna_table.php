<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('nna', function (Blueprint $table) {
            $table->id();
            $table->string('rut')->unique();
            $table->string('nombres');
            $table->string('apellidos');
            $table->integer('edad');
            $table->enum('sexo', ['Masculino', 'Femenino']);
            $table->foreignId('institucion_id')->nullable()->constrained('instituciones_ejecutoras')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('nna');
    }
};


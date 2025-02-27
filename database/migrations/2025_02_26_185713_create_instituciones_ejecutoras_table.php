<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('instituciones_ejecutoras', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_fantasia');
            $table->string('nombre_legal');
            $table->string('rut')->unique();
            $table->string('telefono')->nullable();
            $table->string('email')->nullable();
            $table->foreignId('territorio_id')->constrained('territorios')->onDelete('cascade');
            $table->integer('plazas')->default(0);
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('instituciones_ejecutoras');
    }
};


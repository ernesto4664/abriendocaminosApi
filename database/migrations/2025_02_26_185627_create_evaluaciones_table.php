<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up() {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('planes_intervencion')->onDelete('cascade');
            $table->string('nombre');
            $table->timestamps();
        });
    }

    public function down() {
        Schema::dropIfExists('evaluaciones');
    }
};


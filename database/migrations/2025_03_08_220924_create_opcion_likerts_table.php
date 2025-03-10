<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('opciones_likert', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subpregunta_id')->constrained('respuestas_subpreguntas')->onDelete('cascade');
            $table->string('label');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opcion_likerts');
    }
};

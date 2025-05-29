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
        Schema::create('registro_aspl', function (Blueprint $table) {
            $table->id();
            $table->string('rut_ppl')->unique();
            $table->string('dv_ppl');
            $table->unsignedBigInteger('asignar_nna');
            $table->string('nombres_ppl');
            $table->string('apellidos_ppl');
            $table->string('sexo_ppl');
            $table->string('centro_penal');
            $table->string('region_penal');
            $table->string('nacionalidad_ppl');
            $table->boolean('participa_programa');
            $table->text('motivo_no_participa')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_aspl');
    }
};

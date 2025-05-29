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
        Schema::create('registro_cuidador', function (Blueprint $table) {
            $table->id();
            $table->string('rut', 20);
            $table->string('dv', 2);
            $table->string('nombres');
            $table->string('apellidos');
            $table->unsignedBigInteger('asignar_nna'); // FK al NNA (tabla donde están los NNA)
            $table->enum('sexo', ['M', 'F']);
            $table->integer('edad')->unsigned();
            $table->string('parentesco_aspl');
            $table->string('parentesco_nna');
            $table->string('nacionalidad');
            $table->boolean('participa_programa')->default(true);
            $table->text('motivo_no_participa')->nullable();
            $table->string('documento_firmado')->nullable(); // path archivo
            $table->timestamps();
    });
 }
    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('registro_cuidador');
    }
};

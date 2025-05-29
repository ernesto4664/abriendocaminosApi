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
    Schema::create('registro_nnas', function (Blueprint $table) {
        $table->id();

        // Relación con usuarios_institucion y instituciones_ejecutoras
        $table->foreignId('profesional_id')->constrained('usuarios_institucion')->onDelete('cascade');
        $table->foreignId('institucion_id')->constrained('instituciones_ejecutoras')->onDelete('cascade');

        $table->string('rut')->unique(); // rut con dv junto
        $table->string('dv'); // rut con dv junto
        $table->string('nombres');
        $table->string('apellidos');
        $table->integer('edad');
        $table->enum('sexo', ['M', 'F']);
        $table->string('vias_ingreso');

        $table->string('parentesco_aspl')->nullable();
        $table->string('parentesco_cp')->nullable();

        $table->string('nacionalidad');
        $table->boolean('participa_programa');
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
        Schema::dropIfExists('registro_nnas');
    }
};

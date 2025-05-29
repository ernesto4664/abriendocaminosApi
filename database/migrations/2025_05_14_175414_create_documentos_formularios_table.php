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
    Schema::create('documentos_formularios', function (Blueprint $table) {
        $table->id();
        $table->string('nombre');
        $table->enum('formulario_destino', ['NNA','ASPL','Cuidador/a Principal']);
        $table->string('ruta_archivo');
        $table->timestamps();
    });
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documentos_formularios');
    }
};

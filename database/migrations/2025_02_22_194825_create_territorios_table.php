<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('territorios', function (Blueprint $table) {
            $table->id();
            $table->string('nombre_territorio');
            $table->unsignedBigInteger('comuna_id'); // Relación manual sin cascade delete
            $table->integer('plazas')->nullable();
            $table->enum('linea', ['1', '2']); // Indica si es Línea 1 o Línea 2
            $table->decimal('cuota_1', 10, 2)->nullable();
            $table->decimal('cuota_2', 10, 2)->nullable();
            $table->decimal('total', 10, 2)->nullable();
            $table->timestamps();

            // Definir clave foránea sin onDelete('cascade')
            $table->foreign('comuna_id')->references('id')->on('comunas')->onDelete('restrict');
        });
    }

    public function down()
    {
        Schema::dropIfExists('territorios');
    }
};

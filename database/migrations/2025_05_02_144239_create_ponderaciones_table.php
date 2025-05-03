<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('ponderaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plan_id');
            $table->unsignedBigInteger('evaluacion_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();

            $table->foreign('plan_id')
                  ->references('id')
                  ->on('planes_intervencion')
                  ->onDelete('cascade');

            $table->foreign('evaluacion_id')
                  ->references('id')
                  ->on('evaluaciones')
                  ->onDelete('cascade');

            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('ponderaciones');
    }
};
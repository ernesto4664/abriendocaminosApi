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
        Schema::table('respuestas', function (Blueprint $table) {
            $table->unsignedBigInteger('evaluacion_id')->nullable()->after('profesional_id');
            $table->foreign('evaluacion_id')->references('id')->on('evaluaciones')->onDelete('cascade');
        });
    }
    
    public function down()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            $table->dropForeign(['evaluacion_id']);
            $table->dropColumn('evaluacion_id');
        });
    }
    
};

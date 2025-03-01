<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('territorios', function (Blueprint $table) {
            $table->dropColumn('linea'); // Eliminamos la columna antigua
            $table->unsignedBigInteger('linea_id')->nullable()->after('plazas');
            $table->foreign('linea_id')->references('id')->on('lineasdeintervenciones')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('territorios', function (Blueprint $table) {
            $table->dropForeign(['linea_id']);
            $table->dropColumn('linea_id');
            $table->string('linea')->nullable();
        });
    }
};

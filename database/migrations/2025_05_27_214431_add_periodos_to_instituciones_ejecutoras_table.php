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
        Schema::table('instituciones_ejecutoras', function (Blueprint $table) {
            $table->date('periodo_registro_desde')->nullable();
            $table->date('periodo_registro_hasta')->nullable();
            $table->date('periodo_seguimiento_desde')->nullable();
            $table->date('periodo_seguimiento_hasta')->nullable();
        });
    }

    public function down()
    {
        Schema::table('instituciones_ejecutoras', function (Blueprint $table) {
            $table->dropColumn([
                'periodo_registro_desde',
                'periodo_registro_hasta',
                'periodo_seguimiento_desde',
                'periodo_seguimiento_hasta',
            ]);
        });
    }
};

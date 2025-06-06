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
    Schema::table('territorios', function (Blueprint $table) {
        $table->integer('plazas_disponibles')->nullable()->after('plazas');
    });

    // Actualizar registros existentes
    DB::table('territorios')->update([
        'plazas_disponibles' => DB::raw('plazas')
    ]);
}


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('territorios', function (Blueprint $table) {
            //
        });
    }
};

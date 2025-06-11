<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class MakeLocationFieldsNullableOnUsuariosInstitucionTable extends Migration
{
    public function up()
    {
        // 1) Desactivar chequeo de FKs
        Schema::disableForeignKeyConstraints();

        Schema::table('usuarios_institucion', function (Blueprint $table) {
            // 2) Ajustar tipos para que coincidan y hacer nullable
            $table->unsignedInteger('region_id')       ->nullable()->change();
            $table->unsignedBigInteger('provincia_id') ->nullable()->change();
            $table->unsignedBigInteger('comuna_id')    ->nullable()->change();
            $table->unsignedBigInteger('institucion_id')->nullable()->change();
        });

        // 3) Reactivar chequeo de FKs
        Schema::enableForeignKeyConstraints();
    }

    public function down()
    {
        Schema::disableForeignKeyConstraints();

        Schema::table('usuarios_institucion', function (Blueprint $table) {
            $table->unsignedBigInteger('region_id')       ->nullable(false)->change();
            $table->unsignedBigInteger('provincia_id')    ->nullable(false)->change();
            $table->unsignedBigInteger('comuna_id')       ->nullable(false)->change();
            $table->unsignedBigInteger('institucion_id')  ->nullable(false)->change();
        });

        Schema::enableForeignKeyConstraints();
    }
}

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
            \DB::statement("ALTER TABLE respuestas MODIFY COLUMN tipo ENUM(
                'texto',
                'barra_satisfaccion',
                '5emojis',
                'si_no',
                'si_no_noestoyseguro',
                'likert',
                'numero',
                'opcion'
            ) DEFAULT 'texto';");
        });
    }

    public function down()
    {
        Schema::table('respuestas', function (Blueprint $table) {
            \DB::statement("ALTER TABLE respuestas MODIFY COLUMN tipo ENUM(
                'texto',
                'barra_satisfaccion',
                '5emojis',
                'si_no',
                'si_no_noestoyseguro'
            ) DEFAULT 'texto';");
        });
    }
};

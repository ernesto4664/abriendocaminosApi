<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('territorios', function (Blueprint $table) {
            $table->unsignedBigInteger('cod_territorio')->nullable()->after('nombre_territorio');
        });
    }

    public function down(): void
    {
        Schema::table('territorios', function (Blueprint $table) {
            $table->dropColumn('cod_territorio');
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('territorios', function (Blueprint $table) {
            $table->unsignedBigInteger('cuota_1')->nullable()->change();
            $table->unsignedBigInteger('cuota_2')->nullable()->change();
            $table->unsignedBigInteger('total')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('territorios', function (Blueprint $table) {
            $table->decimal('cuota_1', 10, 2)->nullable()->change();
            $table->decimal('cuota_2', 10, 2)->nullable()->change();
            $table->decimal('total', 10, 2)->nullable()->change();
        });
    }
};


<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up() {
        Schema::table('instituciones_ejecutoras', function (Blueprint $table) {
            $table->unsignedBigInteger('planesdeintervencion_id')->nullable()->after('territorio_id');
            $table->foreign('planesdeintervencion_id')->references('id')->on('planes_intervencion')->onDelete('set null');
        });
    }
    
    public function down() {
        Schema::table('instituciones_ejecutoras', function (Blueprint $table) {
            $table->dropForeign(['planesdeintervencion_id']);
            $table->dropColumn('planesdeintervencion_id');
        });
    }
   
};

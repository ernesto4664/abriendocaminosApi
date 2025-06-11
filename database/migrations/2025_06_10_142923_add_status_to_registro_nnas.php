<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('registro_nnas', function (Blueprint $table) {
            $table->string('status')->default('activo')->after('documento_firmado');
        });

        // (Esto no es necesario ya que el default ya es 'activo', pero se puede dejar si quieres asegurar)
        DB::table('registro_nnas')->update(['status' => 'activo']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('registro_nnas', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};

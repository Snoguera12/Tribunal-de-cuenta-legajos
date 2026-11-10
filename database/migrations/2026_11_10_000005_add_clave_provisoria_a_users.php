<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // los usuarios que ya existen no se obligan a cambiar la clave
            $table->boolean('debe_cambiar_clave')->default(false);
            $table->timestamp('clave_cambiada_en')->nullable();
            $table->timestamp('primer_ingreso_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['debe_cambiar_clave', 'clave_cambiada_en', 'primer_ingreso_en']);
        });
    }
};

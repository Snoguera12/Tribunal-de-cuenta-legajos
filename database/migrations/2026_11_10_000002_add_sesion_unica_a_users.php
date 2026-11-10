<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // sesión única: huella de la sesión abierta (no el id real), desde dónde y cuándo la usó por última vez
            $table->string('sesion_activa_id', 40)->nullable();
            $table->string('sesion_activa_ip', 45)->nullable();
            $table->timestamp('sesion_activa_desde')->nullable();
            $table->timestamp('ultima_actividad_en')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['sesion_activa_id', 'sesion_activa_ip', 'sesion_activa_desde', 'ultima_actividad_en']);
        });
    }
};

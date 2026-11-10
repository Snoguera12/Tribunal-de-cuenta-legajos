<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // un usuario deshabilitado no puede ingresar (no se borra: queda el registro)
            $table->boolean('habilitado')->default(true)->after('rol');
            // manual o bloqueo_login
            $table->string('deshabilitado_motivo', 20)->nullable()->after('habilitado');
            $table->timestamp('deshabilitado_en')->nullable()->after('deshabilitado_motivo');
        });

        // estado de los intentos de clave de cada usuario
        Schema::create('login_intentos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->unsignedSmallInteger('fallos_tanda')->default(0);
            $table->unsignedSmallInteger('fallos_totales')->default(0);
            $table->unsignedSmallInteger('tandas_agotadas')->default(0);
            $table->unsignedSmallInteger('reinicios')->default(0);
            $table->timestamp('ultimo_fallo_en')->nullable();
            $table->timestamp('proximo_intento_en')->nullable();
            $table->timestamp('bloqueado_hasta')->nullable();
            $table->boolean('permanente')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_intentos');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['habilitado', 'deshabilitado_motivo', 'deshabilitado_en']);
        });
    }
};

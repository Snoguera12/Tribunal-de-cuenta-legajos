<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        if (! Schema::hasColumn('users', 'debe_cambiar_clave')) {
            Schema::table('users', function (Blueprint $table) {
                // los usuarios que ya existen no se obligan a cambiar la clave
                $table->boolean('debe_cambiar_clave')->default(false);
                $table->timestamp('clave_cambiada_en')->nullable();
                $table->timestamp('primer_ingreso_en')->nullable();

                // sesion unica
                $table->string('sesion_activa_id', 40)->nullable();
                $table->string('sesion_activa_ip', 45)->nullable();
                $table->timestamp('sesion_activa_desde')->nullable();
                $table->timestamp('ultima_actividad_en')->nullable();
            });
        }

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
            $table->dropColumn(['debe_cambiar_clave', 'clave_cambiada_en', 'primer_ingreso_en',
                'sesion_activa_id', 'sesion_activa_ip', 'sesion_activa_desde', 'ultima_actividad_en']);
        });
    }
};

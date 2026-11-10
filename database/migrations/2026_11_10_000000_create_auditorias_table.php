<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();

            // quien
            $table->string('actor_tipo', 20)->default('usuario');
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_nombre')->nullable();
            $table->string('actor_rol', 30)->nullable();

            // que
            $table->string('evento', 40);
            $table->string('categoria', 20)->default('sistema');
            $table->string('severidad', 10)->default('info');
            $table->text('descripcion');

            // sobre que registro (sin FK: la auditoria no depende de que el registro exista)
            $table->string('modelo', 60)->nullable();
            $table->unsignedBigInteger('modelo_id')->nullable();

            // antes y despues
            $table->json('valores_anteriores')->nullable();
            $table->json('valores_nuevos')->nullable();
            $table->json('extra')->nullable();

            // desde donde
            $table->string('ip', 45)->nullable();
            $table->string('mac', 17)->nullable();
            $table->text('navegador')->nullable();
            $table->string('sistema_operativo', 40)->nullable();
            $table->string('dispositivo', 20)->nullable();
            $table->text('url')->nullable();
            $table->string('metodo', 10)->nullable();
            $table->string('sesion', 16)->nullable();
            $table->string('peticion', 36)->nullable();

            // cadena de hashes: cada registro lleva el hash del anterior, si tocan uno se rompe la cadena
            $table->string('hash_previo', 64)->nullable();
            $table->string('hash', 64)->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('evento');
            $table->index('severidad');
            $table->index('actor_id');
            $table->index('ip');
            $table->index(['modelo', 'modelo_id']);
            $table->index('created_at');
        });

        // una fila por cada tanda de auditoria que se paso a un archivo comprimido
        Schema::create('auditoria_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('desde_id');
            $table->unsignedBigInteger('hasta_id');
            $table->unsignedInteger('cantidad');
            // hash del ultimo registro archivado (el siguiente lo trae como hash_previo)
            $table->string('ultimo_hash', 64);
            $table->string('archivo');
            $table->string('sha256', 64);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auditoria_archivos');
        Schema::dropIfExists('auditorias');
    }
};

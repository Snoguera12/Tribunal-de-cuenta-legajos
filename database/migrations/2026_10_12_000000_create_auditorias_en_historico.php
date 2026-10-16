<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // va en la base aparte
    protected $connection = 'historico';


    public function up(): void
    {
        Schema::connection('historico')->create('auditorias', function (Blueprint $table) {
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

            // sobre que registro
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

            $table->timestamp('created_at')->useCurrent();

            $table->index('evento');
            $table->index('severidad');
            $table->index('actor_id');
            $table->index('ip');
            $table->index(['modelo', 'modelo_id']);
            $table->index('created_at');
        });
    }


    public function down(): void
    {
        Schema::connection('historico')->dropIfExists('auditorias');
    }
};

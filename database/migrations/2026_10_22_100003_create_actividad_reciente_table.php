<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// ultimos agentes y usuarios que cada persona de RRHH vio o modifico (una fila por usuario + registro)
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('actividad_reciente', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('tipo', 10);            // agente | usuario
            $table->unsignedBigInteger('registro_id'); // id de la persona o del usuario
            $table->timestamp('visto_en')->nullable();
            $table->timestamp('modificado_en')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'tipo', 'registro_id']);
            $table->index(['user_id', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('actividad_reciente');
    }
};

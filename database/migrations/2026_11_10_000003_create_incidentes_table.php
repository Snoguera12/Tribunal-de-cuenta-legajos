<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidentes', function (Blueprint $table) {
            $table->id();

            $table->string('tipo', 40);
            $table->string('severidad', 10)->default('alta');
            $table->text('descripcion');

            // a quién se le atribuye
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_nombre')->nullable();
            $table->string('ip', 45)->nullable();
            $table->string('mac', 17)->nullable();

            // vínculo con el registro de auditoría que lo disparó
            $table->unsignedBigInteger('auditoria_id')->nullable();
            $table->json('detalle')->nullable();

            // seguimiento
            $table->string('estado', 15)->default('abierto');
            $table->text('notas')->nullable();
            $table->unsignedBigInteger('resuelto_por')->nullable();
            $table->timestamp('resuelto_en')->nullable();

            $table->timestamps();

            $table->index('estado');
            $table->index('tipo');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidentes');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained()->restrictOnDelete();
            $table->unsignedBigInteger('solicitud_id');
            $table->string('tipo_licencia', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('dias_solicitados');
            $table->string('motivo', 500)->nullable();
            $table->string('nro_resolucion', 50)->nullable();
            $table->date('fecha_aprobacion');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licencias');
    }
};

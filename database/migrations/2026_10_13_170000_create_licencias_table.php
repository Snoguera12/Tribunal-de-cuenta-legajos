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
            $table->foreignId('legajo_id')->constrained()->onDelete('cascade');

            // Qué cliente de la API la envió (sistema de licencias del otro grupo).
            $table->foreignId('api_client_id')->nullable()->constrained('api_clients')->nullOnDelete();

            // Datos que envía el otro sistema (licencias ya aprobadas allá)
            $table->unsignedBigInteger('id_solicitud');
            $table->string('tipo_licencia', 100);
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->unsignedSmallInteger('dias_solicitados');
            $table->string('motivo', 500)->nullable();
            $table->string('nro_resolucion', 50)->nullable();
            $table->date('fecha_aprobacion');

            // Lo recibido tal cual, para auditoría
            $table->json('payload_original')->nullable();

            $table->timestamps();

            // Idempotencia: el mismo envío del mismo cliente no se duplica.
            $table->unique(['api_client_id', 'id_solicitud']);
            $table->index(['legajo_id', 'fecha_inicio']);
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

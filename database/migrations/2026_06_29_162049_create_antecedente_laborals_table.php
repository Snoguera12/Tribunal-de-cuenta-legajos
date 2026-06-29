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
        Schema::create('antecedente_laborals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete()->after('id');
            $table->string('empleador')->after('persona_id');
            $table->string('lugar_de_trabajo')->after('empleador');
            $table->string('cargo')->nullable()->after('lugar_de_trabajo');
            $table->date('fecha_inicio')->nullable()->after('cargo');
            $table->date('fecha_fin')->nullable()->after('fecha_inicio');
            $table->string('motivo_egreso')->nullable()->after('fecha_fin');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('antecedente_laborals');
    }
};

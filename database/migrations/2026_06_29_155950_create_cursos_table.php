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
        Schema::create('cursos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id');
            $table->foreignId('institucion_id')->nullable();
            $table->string('institucion_otro', 300)->nullable();
            $table->string('nombre');
            $table->string('duracion');
            $table->date('fecha');
            $table->boolean('tiene_certificado');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cursos');
    }
};

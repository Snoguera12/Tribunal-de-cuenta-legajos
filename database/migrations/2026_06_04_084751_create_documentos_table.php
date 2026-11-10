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
        Schema::create('documentos', function (Blueprint $table) {
            $table->id();
            $table->string('ruta')->nullable();
            $table->string('nombre_original')->nullable();
            $table->text('descripcion')->nullable();
            $table->integer('tipodoc')->nullable();
            $table->timestamps();
        });

        // Un documento puede estar asignado a varios legajos sin repetir el archivo
        Schema::create('documento_legajo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('documento_id')->constrained('documentos')->restrictOnDelete();
            $table->foreignId('legajo_id')->constrained('legajos')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['documento_id', 'legajo_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documento_legajo');
        Schema::dropIfExists('documentos');
    }
};

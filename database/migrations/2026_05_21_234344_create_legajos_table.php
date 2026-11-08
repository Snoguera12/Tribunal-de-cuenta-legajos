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
        Schema::create('legajos', function (Blueprint $table) {
            $table->id();
            $table->integer("num_legajo")->unique();
            $table->boolean("estado");
            $table->dateTime("fecha_de_ingreso");
            $table->integer("tipo_contrato")->nullable();
            $table->foreignId("persona_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("categoria_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("cargo_id")->nullable()->constrained()->nullOnDelete();
            $table->foreignId("area_id")->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('legajos');
    }
};

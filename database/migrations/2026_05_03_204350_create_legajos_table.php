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
            $table->string("número de legajo", 16);
            $table->string("estado", 4);
            $table->date("fecha_de_ingreso");
            $table->foreignId("cargo_id")->constrained()->onDelete("cascade");
            $table->string("categoria", 3);
            $table->foreignId("persona_id")->constrained()->onDelete("cascade");
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

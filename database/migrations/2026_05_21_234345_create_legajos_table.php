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
            $table->integer("num_legajo")->nullable()->unique();
            $table->boolean("estado");
            $table->dateTime("fecha_de_ingreso");
            $table->foreignId("persona_id")->constrained()->onDelete("cascade");
            $table->foreignId("categoria_id")->constrained()->onDelete("cascade");
            $table->foreignId("cargo_id")->constrained()->onDelete("cascade");
            $table->foreignId("area_id")->constrained()->onDelete("cascade");
            $table->foreignId("titulo_id")->constrained()->onDelete("cascade");
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

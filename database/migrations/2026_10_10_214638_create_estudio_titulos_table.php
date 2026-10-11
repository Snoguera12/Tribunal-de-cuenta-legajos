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
        Schema::create('estudio_titulos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('titulo_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('titulo_otro', 100)->nullable();
            $table->unique(['estudio_id', 'titulo_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudio_titulos');
    }
};

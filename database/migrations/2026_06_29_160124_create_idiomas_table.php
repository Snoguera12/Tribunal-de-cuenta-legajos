<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de idiomas (como "titulos")
        Schema::create('idiomas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100)->unique();
            $table->timestamps();
        });

        // Idioma de cada persona (como "estudio_titulos")
        Schema::create('persona_idiomas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('persona_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('idioma_id')->nullable()->constrained()->nullOnDelete();
            $table->string('idioma_otro', 100)->nullable();
            $table->integer('nivel');
            $table->unique(['persona_id', 'idioma_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('persona_idiomas');
        Schema::dropIfExists('idiomas');
    }
};

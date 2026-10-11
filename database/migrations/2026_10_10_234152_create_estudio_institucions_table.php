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
        Schema::create('estudio_institucions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('estudio_id')->constrained()->cascadeOnDelete();
            $table->foreignId('institucion_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('institucion_otro', 300)->nullable();
            $table->unique(['estudio_id', 'institucion_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudio_institucions');
    }
};

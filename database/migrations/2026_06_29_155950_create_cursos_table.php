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
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete()->after('id');
            $table->string('nombre')->after('persona_id');
            $table->string('institucion')->nullable()->after('nombre');
            $table->string('duracion')->nullable()->after('institucion');
            $table->date('fecha')->nullable()->after('duracion');
            $table->boolean('tiene_certificado')->default(false)->after('fecha');
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

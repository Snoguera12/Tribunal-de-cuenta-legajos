<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('familiars', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->integer('dni',)->nullable();
            $table->integer('parentesco');
            $table->date('fecha_de_nacimiento')->nullable();
            $table->boolean('vive');
            $table->foreignId('persona_id')->constrained('personas')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        // (antes apuntaba a una tabla "familiares" que no existe y migrate:rollback fallaba)
        Schema::dropIfExists('familiars');
    }
};

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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('familiares', function (Blueprint $table) {
            $table->dropForeign(['persona_id']);
            $table->dropColumn(['nombre', 'apellido', 'dni', 'parentesco', 'fecha_de_nacimiento', 'vive', 'persona_id']);
        });
        Schema::dropIfExists('familiars');
    }
};

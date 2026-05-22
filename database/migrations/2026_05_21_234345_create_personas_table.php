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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();
            $table->string("nombre", 30);
            $table->string("apellido", 30);
            $table->integer("dni")->unique();
            $table->integer("cuil")->unique();
            $table->string("email", 50)->nullable();
            $table->integer('genero');
            $table->date("fecha_de_nacimiento");
            $table->string("domicilio", 100)->nullable();
            $table->string("telefono", 20)->nullable();
            $table->string("telefono_emergencia", 20)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas');
    }
};

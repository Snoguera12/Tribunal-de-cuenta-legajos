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
            $table->string("nombre", 40);
            $table->string("dni", 12)->unique();
            $table->date("fecha_de_nacimiento");
            $table->string("estado", 20);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('familiars');
    }
};

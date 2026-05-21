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
        Schema::create('historialbajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId("legajo_id")->constrained()->onDelete("cascade");
            $table->string("descripcion", 500);
            $table->dateTime('fecha_baja');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('historialbajas');
    }
};

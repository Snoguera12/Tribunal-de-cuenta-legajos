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
        // esta migracion se adelanto (estudios y cursos la necesitan y MySQL exige que exista antes).
        // En las bases que ya la tenian creada con el nombre anterior, no hace nada.
        if (Schema::hasTable('institucions')) {
            return;
        }

        Schema::create('institucions', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 300)->unique();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institucions');
    }
};

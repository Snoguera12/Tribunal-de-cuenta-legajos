<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Algunas instalaciones del proyecto tienen la tabla "documentos" creada
     * sin la columna "ruta" (quedó desincronizada respecto de la migración
     * original create_documentos_table). Esta migración la agrega si falta,
     * sin tocar ni borrar ninguna otra columna ni ningún otro dato.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('documentos', 'ruta')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->string('ruta')->nullable()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documentos', 'ruta')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->dropColumn('ruta');
            });
        }
    }
};

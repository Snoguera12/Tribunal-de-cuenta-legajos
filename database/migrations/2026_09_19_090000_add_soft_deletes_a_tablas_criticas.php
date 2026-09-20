<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // -mejor seguridad para no perder datos- (borrado suave)
    // en estas tablas, borrar no elimina de verdad, solo marca una fecha
    private array $tablas = [
        'personas', 'legajos', 'documentos', 'historialbajas', 'familiars',
        'estudios', 'titulos', 'cursos', 'idiomas', 'antecedente_laborals',
        'users',
    ];

    public function up(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasTable($tabla) && !Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->softDeletes();
                });
            }
        }
    }

    public function down(): void
    {
        foreach ($this->tablas as $tabla) {
            if (Schema::hasTable($tabla) && Schema::hasColumn($tabla, 'deleted_at')) {
                Schema::table($tabla, function (Blueprint $table) {
                    $table->dropSoftDeletes();
                });
            }
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// DNI que el sistema de licencias indica para el PDF: sugiere a quien asignarlo (RRHH lo confirma)
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('documentos', 'dni_sugerido')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->string('dni_sugerido', 12)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('documentos', 'dni_sugerido')) {
            Schema::table('documentos', function (Blueprint $table) {
                $table->dropColumn('dni_sugerido');
            });
        }
    }
};

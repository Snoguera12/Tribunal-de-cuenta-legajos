<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'habilitado')) {
                $table->boolean('habilitado')->default(true)->after('rol');
                // manual, baja_legajo o persona_eliminada
                $table->string('deshabilitado_motivo', 20)->nullable()->after('habilitado');
                $table->timestamp('deshabilitado_en')->nullable()->after('deshabilitado_motivo');
            }
        });
    }


    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['habilitado', 'deshabilitado_motivo', 'deshabilitado_en']);
        });
    }
};

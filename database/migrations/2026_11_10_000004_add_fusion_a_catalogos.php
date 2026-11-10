<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // al fusionar un catálogo ya no se borra el registro: se marca en cuál se fusionó
        foreach (['titulos', 'institucions', 'idiomas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->unsignedBigInteger('fusionado_en_id')->nullable()->index();
                $table->timestamp('fusionado_en')->nullable();
            });
        }

        // un título repetido en un estudio se descarta (queda guardado, pero no se muestra)
        Schema::table('estudio_titulos', function (Blueprint $table) {
            $table->timestamp('descartado_en')->nullable();
        });
    }

    public function down(): void
    {
        foreach (['titulos', 'institucions', 'idiomas'] as $tabla) {
            Schema::table($tabla, function (Blueprint $table) {
                $table->dropIndex(['fusionado_en_id']);
                $table->dropColumn(['fusionado_en_id', 'fusionado_en']);
            });
        }

        Schema::table('estudio_titulos', function (Blueprint $table) {
            $table->dropColumn('descartado_en');
        });
    }
};

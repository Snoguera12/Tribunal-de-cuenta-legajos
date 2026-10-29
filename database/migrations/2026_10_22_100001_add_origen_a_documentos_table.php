<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// de donde vino el documento (por ejemplo, el sistema de licencias) y con que numero lo conoce el otro sistema
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            if (! Schema::hasColumn('documentos', 'nombre_original')) {
                $table->string('nombre_original')->nullable();
            }
            if (! Schema::hasColumn('documentos', 'origen')) {
                $table->string('origen', 30)->nullable();
            }
            if (! Schema::hasColumn('documentos', 'api_client_id')) {
                $table->unsignedBigInteger('api_client_id')->nullable();
            }
            if (! Schema::hasColumn('documentos', 'id_externo')) {
                $table->string('id_externo', 60)->nullable();
            }
        });

        Schema::table('documentos', function (Blueprint $table) {
            // un mismo numero del otro sistema no puede entrar dos veces
            $table->unique(['api_client_id', 'id_externo'], 'documentos_origen_externo_unico');
        });
    }

    public function down(): void
    {
        Schema::table('documentos', function (Blueprint $table) {
            $table->dropUnique('documentos_origen_externo_unico');
            $table->dropColumn(['origen', 'api_client_id', 'id_externo']);
        });
    }
};

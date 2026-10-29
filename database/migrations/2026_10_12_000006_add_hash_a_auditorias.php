<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $connection = 'historico';


    public function up(): void
    {
        if (Schema::connection('historico')->hasColumn('auditorias', 'hash')) {
            return;
        }

        Schema::connection('historico')->table('auditorias', function (Blueprint $table) {
            // cada registro lleva el hash del anterior: si tocan uno, se rompe la cadena
            $table->string('hash_previo', 64)->nullable();
            $table->string('hash', 64)->nullable();
        });
    }


    public function down(): void
    {
        Schema::connection('historico')->table('auditorias', function (Blueprint $table) {
            $table->dropColumn(['hash_previo', 'hash']);
        });
    }
};

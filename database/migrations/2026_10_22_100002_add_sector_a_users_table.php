<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// sector del administrador: 'tic' o 'rrhh' (queda vacio en los demas usuarios)
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'sector')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('sector', 10)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'sector')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('sector');
            });
        }
    }
};

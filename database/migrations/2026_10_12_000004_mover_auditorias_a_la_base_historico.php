<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    // si en la primera version la tabla auditorias quedo en la base principal,
    // se pasan los registros a la base aparte y se borra de la principal
    public function up(): void
    {
        $principal = config('database.default');

        if ($principal === 'historico' || ! Schema::connection($principal)->hasTable('auditorias')) {
            return;
        }

        $columnasNuevas = Schema::connection('historico')->getColumnListing('auditorias');

        DB::connection($principal)->table('auditorias')->orderBy('id')->chunk(500, function ($filas) use ($columnasNuevas) {

            foreach ($filas as $fila) {
                $fila = array_intersect_key((array) $fila, array_flip($columnasNuevas));

                if (! DB::connection('historico')->table('auditorias')->where('id', $fila['id'])->exists()) {
                    DB::connection('historico')->table('auditorias')->insert($fila);
                }
            }
        });

        Schema::connection($principal)->drop('auditorias');
    }


    public function down(): void
    {
        //
    }
};

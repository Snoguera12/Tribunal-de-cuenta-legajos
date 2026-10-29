<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// un documento (un solo archivo) puede quedar asignado a varios legajos sin repetirse
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('documento_legajo')) {
            Schema::create('documento_legajo', function (Blueprint $table) {
                $table->id();
                $table->foreignId('documento_id')->constrained('documentos')->cascadeOnDelete();
                $table->foreignId('legajo_id')->constrained('legajos')->cascadeOnDelete();
                $table->foreignId('asignado_por')->nullable();
                $table->timestamps();

                $table->unique(['documento_id', 'legajo_id']);
                $table->index('legajo_id');
            });
        }

        // los documentos que ya tenian legajo pasan a la tabla nueva (una sola vez, por lotes)
        $legajosQueExisten = array_flip(DB::table('legajos')->pluck('id')->all());
        $ahora = now();

        DB::table('documentos')->whereNotNull('legajo_id')->orderBy('id')->select('id', 'legajo_id')->chunk(1000, function ($filas) use ($legajosQueExisten, $ahora) {
            $lote = [];

            foreach ($filas as $fila) {
                if (isset($legajosQueExisten[$fila->legajo_id])) {
                    $lote[] = ['documento_id' => $fila->id, 'legajo_id' => $fila->legajo_id, 'created_at' => $ahora, 'updated_at' => $ahora];
                }
            }

            if ($lote) {
                DB::table('documento_legajo')->insertOrIgnore($lote);
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documento_legajo');
    }
};

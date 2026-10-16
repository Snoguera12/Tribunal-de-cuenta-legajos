<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    protected $connection = 'historico';


    // un renglon por cada tanda de auditoria que se paso a un archivo comprimido
    public function up(): void
    {
        Schema::connection('historico')->create('auditoria_archivos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('desde_id');
            $table->unsignedBigInteger('hasta_id');
            $table->unsignedInteger('cantidad');
            // hash del ultimo registro archivado (el siguiente lo trae como hash_previo)
            $table->string('ultimo_hash', 64);
            $table->string('archivo');
            $table->string('sha256', 64);
            $table->timestamp('created_at')->useCurrent();
        });
    }


    public function down(): void
    {
        Schema::connection('historico')->dropIfExists('auditoria_archivos');
    }
};

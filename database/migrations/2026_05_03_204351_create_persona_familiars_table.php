<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('persona_familiars', function (Blueprint $table) {
            $table->id();
            $table->string("parentesco", 20);
            $table->foreignId("persona_id")->constrained()->onDelete("cascade");
            $table->foreignId("familiar_id")->constrained()->onDelete("cascade");
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('persona_familiars');
    }
};

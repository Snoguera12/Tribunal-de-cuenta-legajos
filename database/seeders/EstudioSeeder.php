<?php

namespace Database\Seeders;

use App\Models\Estudio;
use Illuminate\Database\Seeder;

class EstudioSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Estudio::create([
            'institucion' => 'UNSE',
            'nivel_estudio' => 7,
            'fecha_fin' => '2025-12-12',
            'persona_id' => 15,
        ]);
    }
}

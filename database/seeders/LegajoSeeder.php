<?php

namespace Database\Seeders;

use App\Models\Legajo;
use Illuminate\Database\Seeder;

class LegajoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Legajo::create([
            'num_legajo' => 1002,
            'estado' => 1,
            'fecha_de_ingreso' => '2026-09-08 10:38:06',
            'tipo_contrato' => 2,
            'persona_id' => 15,
            'categoria_id' => 11,
            'cargo_id' => 5,
            'area_id' => 1,
        ]);
    }
}

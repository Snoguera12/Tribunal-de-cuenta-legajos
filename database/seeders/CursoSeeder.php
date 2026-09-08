<?php

namespace Database\Seeders;

use App\Models\Curso;
use Illuminate\Database\Seeder;

class CursoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Curso::create([
            'persona_id' => 15,
            'nombre' => 'Carpinteria',
            'institucion' => 'Instituto Banda',
            'duracion' => '1 año',
            'fecha' => '2024-11-30',
            'tiene_certificado' => 1,
        ]);
    }
}

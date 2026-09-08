<?php

namespace Database\Seeders;

use App\Models\Titulo;
use Illuminate\Database\Seeder;

class TituloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Titulo::create([
            'nombre' => 'Ingeniero Civil',
            'estudio_id' => 1,
        ]);
    }
}

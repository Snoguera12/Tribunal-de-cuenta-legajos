<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            AreaSeeder::class,
            CargoSeeder::class,
            CategoriaSeeder::class,
            CursoSeeder::class,
            PersonaSeeder::class,
            LegajoSeeder::class,
            EstudioSeeder::class,
            TituloSeeder::class,
            UserSeeder::class,
        ]);
    }
}
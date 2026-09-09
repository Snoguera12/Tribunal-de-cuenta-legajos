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
            UserSeeder::class,
            PersonalSantiagoSeeder::class,
            //LegajoSeeder::class,
        ]);
    }
}
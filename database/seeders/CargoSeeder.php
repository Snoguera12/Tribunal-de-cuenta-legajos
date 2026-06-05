<?php

namespace Database\Seeders;

use App\Models\Cargo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CargoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Cargo::create(['nombre' => 'Archivo']);
        Cargo::create(['nombre' => 'Auditoria']);
        Cargo::create(['nombre' => 'Comision de estudios 1']);
        Cargo::create(['nombre' => 'Comision de estudios 2']);
        Cargo::create(['nombre' => 'Computos']);
        Cargo::create(['nombre' => 'Fiscal General 1']);
        Cargo::create(['nombre' => 'Fiscal General 2']);
        Cargo::create(['nombre' => 'Habilitación']);
        Cargo::create(['nombre' => 'Mesa de entradas']);
        Cargo::create(['nombre' => 'Municipios']);
        Cargo::create(['nombre' => 'Personal']);
        Cargo::create(['nombre' => 'Rendición de cuentas']);
        Cargo::create(['nombre' => 'Secretaria General']);
        Cargo::create(['nombre' => 'Servicios Generales']);
    }
}

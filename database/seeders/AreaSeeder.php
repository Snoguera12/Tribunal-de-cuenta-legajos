<?php

namespace Database\Seeders;

use App\Models\Area;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Area::create(['nombre' => 'Archivo']);
        Area::create(['nombre' => 'Auditoria']);
        Area::create(['nombre' => 'Comision de estudios 1']);
        Area::create(['nombre' => 'Comision de estudios 2']);
        Area::create(['nombre' => 'Computos']);
        Area::create(['nombre' => 'Fiscal General 1']);
        Area::create(['nombre' => 'Fiscal General 2']);
        Area::create(['nombre' => 'Habilitación']);
        Area::create(['nombre' => 'Mesa de entradas']);
        Area::create(['nombre' => 'Municipios']);
        Area::create(['nombre' => 'Personal']);
        Area::create(['nombre' => 'Rendición de cuentas']);
        Area::create(['nombre' => 'Secretaria General']);
        Area::create(['nombre' => 'Servicios Generales']);
    }
}

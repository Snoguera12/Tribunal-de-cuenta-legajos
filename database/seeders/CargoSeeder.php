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
        Cargo::create(['nombre' => 'Administrativo']);
        Cargo::create(['nombre' => 'Auditoria']);
        Cargo::create(['nombre' => 'Comisión de Gobierno']);
        Cargo::create(['nombre' => 'Consejero/a de Cuentas']);
        Cargo::create(['nombre' => 'Coordinador Ejecutivo']);
        Cargo::create(['nombre' => 'Fiscal Jefe/a']);
        Cargo::create(['nombre' => 'Gabinete Técnico']);
        Cargo::create(['nombre' => 'Informático']);
        Cargo::create(['nombre' => 'Interventor']);
        Cargo::create(['nombre' => 'Oficial Mayor de Estudios']);
        Cargo::create(['nombre' => 'Oficial Principal de Despacho']);
        Cargo::create(['nombre' => 'Operador']);
        Cargo::create(['nombre' => 'Ordenanza']);
        Cargo::create(['nombre' => 'Presidente/a']);
        Cargo::create(['nombre' => 'Relator Mayor Coordinador']);
        Cargo::create(['nombre' => 'Sección de Fiscalización']);
        Cargo::create(['nombre' => 'Secretaria General']);
        Cargo::create(['nombre' => 'Servicio Jurídico del Estado']);
        Cargo::create(['nombre' => 'Vocero']);
    }
}

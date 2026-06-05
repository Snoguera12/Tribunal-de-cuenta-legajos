<?php

namespace Database\Seeders;

use App\Models\Categoria;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CategoriaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Categoria::create([
            'nombre' => 'CAT 1',
            "descripcion" => 'Secretario General - Contador/Fiscal'
        ]);
        Categoria::create([
            'nombre' => 'CAT 2',
            "descripcion" => 'Jefe de Rendición'
        ]);
        Categoria::create([
            'nombre' => 'CAT 3',
            "descripcion" => 'Auditor Junior - Sin Denominación'
        ]);
        Categoria::create([
            'nombre' => 'CAT 4',
            "descripcion" => 'Auditor Semijunior - Sin Denominación (sin título)'
        ]);
        Categoria::create([
            'nombre' => 'CAT 5',
            "descripcion" => 'Jefe de Despacho - Auditor Junior'
        ]);
        Categoria::create([
            'nombre' => 'CAT 6',
            "descripcion" => 'Operador'
        ]);
        Categoria::create([
            'nombre' => 'CAT 7',
            "descripcion" => 'Jefe de Sección'
        ]);
        Categoria::create([
            'nombre' => 'CAT 8',
            "descripcion" => 'Auxiliar Administrativo A - Revisor A'
        ]);
        Categoria::create([
            'nombre' => 'CAT 9',
            "descripcion" => 'Auxiliar Administrativo B - Revisor B'
        ]);
        Categoria::create([
            'nombre' => 'CAT 10',
            "descripcion" => 'Auxiliar Administrativo C - Revisor C'
        ]);
        Categoria::create([
            'nombre' => 'CAT 11',
            "descripcion" => 'Mayordomo'
        ]);
        Categoria::create([
            'nombre' => 'CAT 12',
            "descripcion" => 'Ordenanza - Chofer'
        ]);
    }
}

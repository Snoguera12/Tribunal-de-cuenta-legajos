<?php

namespace Database\Seeders;

use App\Models\Historialbaja;
use App\Models\Legajo;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class LegajoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Legajo::create(
            [
                'num_legajo' => 1001,
                'estado' => true,
                'fecha_de_ingreso' => '2015-03-10 08:00:00',
                'tipo_contrato' => 2, // Permanente
                'persona_id' => 1, // Roberto Suárez
                'categoria_id' => 1, // CAT 1
                'cargo_id' => 17, // Secretaria General
                'area_id' => 13, // Secretaria General
            ],
        );
        Legajo::create(
            [
                'num_legajo' => 1002,
                'estado' => true,
                'fecha_de_ingreso' => '2020-07-01 08:00:00',
                'tipo_contrato' => 1, // Locación
                'persona_id' => 2, // Valentina Acosta
                'categoria_id' => 8, // CAT 8
                'cargo_id' => 1, // Administrativo
                'area_id' => 9, // Mesa de entradas
            ],
        );
        Legajo::create(
            [
                'num_legajo' => 1003,
                'estado' => false,
                'fecha_de_ingreso' => '2010-11-22 08:00:00',
                'tipo_contrato' => 0, // Funcionario
                'persona_id' => 3, // Facundo Ledesma
                'categoria_id' => 2, // CAT 2
                'cargo_id' => 16, // Secretaria General
                'area_id' => 12, // Rendición de cuentas
            ],
        );

        // Registro en el historial de bajas del legajo 1003 (estado = false)
        Historialbaja::create([
            'legajo_id' => 3,
            'user_id' => 5, // Administrador (correofalso123@gmail.com)
            'motivo' => 2, // VencimientoContrato
            'fecha_baja' => '2023-05-30 09:00:00',
        ]);
        Legajo::create(
            [
                'num_legajo' => 1004,
                'estado' => true,
                'fecha_de_ingreso' => '2022-02-14 08:00:00',
                'tipo_contrato' => 2, // Permanente
                'persona_id' => 4, // Camila Paz
                'categoria_id' => 5, // CAT 5
                'cargo_id' => 8, // Informático
                'area_id' => 5, // Computos
            ],
        );
    }
}
<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */

    /* 
    0 = Genérico
    1 = Funcionario
    2 = RRHH
    3 = Empleado
    4 = Administrador
    */

    public function run(): void
    {
        User::factory()->create([
            'name' => 'Empleado',
            'email' => 'empleado@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 1,
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => 'RRHH',
            'email' => 'rrhh@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 2,
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => 'Funcionario',
            'email' => 'funcionario@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 3,
            'email_verified_at' => now(),
        ]);
        User::factory()->create([
            'name' => 'Administador',
            'email' => 'correofalso123@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 4,
            'email_verified_at' => now(),
        ]);
    }
}

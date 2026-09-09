<?php

namespace Database\Seeders;

use App\Models\User;
use Hash;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Pedro',
            'email' => 'pedrogarcia@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 'empleado',
            'email_verified_at' => now(),
            'persona_id' => 1,
        ]);
        User::factory()->create([
            'name' => 'Empleado',
            'email' => 'empleado@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 'empleado',
            'email_verified_at' => now(),
            'persona_id' => NULL,
        ]);
        User::factory()->create([
            'name' => 'Funcionario',
            'email' => 'funcionario@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 'funcionario',
            'email_verified_at' => now(),
            'persona_id' => NULL,
        ]);
        User::factory()->create([
            'name' => 'RRHH',
            'email' => 'rrhh@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 'rrhh',
            'email_verified_at' => now(),
            'persona_id' => NULL,
        ]);
        User::factory()->create([
            'name' => 'Administador',
            'email' => 'correofalso123@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 'administrador',
            'email_verified_at' => now(),
            'persona_id' => NULL,
        ]);
        User::factory()->create([
            'name' => 'Mario',
            'email' => 'mariosantos1@gmail.com',
            'password' => Hash::make('123456'),
            'rol' => 'empleado',
            'email_verified_at' => now(),
            'persona_id' => 15,
        ]);
    }
}

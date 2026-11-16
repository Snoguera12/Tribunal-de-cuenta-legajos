<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // primero el administrador: si faltan sus datos, no se carga nada
        if (! $this->crearAdministrador()) {
            return;
        }

        $this->call([
            AreaSeeder::class,
            CargoSeeder::class,
            CategoriaSeeder::class,
        ]);
    }

    // Único usuario que crea el sistema. Sus datos salen de config('sistema.admin') (variables ADMIN_* del .env).
    // Si ya existe no se toca, para que volver a sembrar no le pise la clave.
    private function crearAdministrador(): bool
    {
        $admin = config('sistema.admin');

        if (blank($admin['nombre']) || blank($admin['email']) || blank($admin['clave'])) {
            $this->command->error('Faltan ADMIN_NOMBRE, ADMIN_EMAIL o ADMIN_CLAVE en el .env: no se cargó ningún dato.');

            return false;
        }

        if (User::where('email', $admin['email'])->exists()) {
            $this->command->info("El administrador {$admin['email']} ya existe: no se modificó.");

            return true;
        }

        User::factory()->create([
            'name' => $admin['nombre'],
            'email' => $admin['email'],
            'password' => $admin['clave'],
            'rol' => 'administrador',
            'email_verified_at' => now(),
            'persona_id' => null,
            'debe_cambiar_clave' => true,
        ]);

        $this->command->info("Administrador {$admin['email']} creado.");

        return true;
    }
}
<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Str;


// para empezar de cero: crea el primer administrador
class SistemaCrearAdmin extends Command
{
    protected $signature = 'sistema:crear-admin {email} {--nombre=Administrador} {--password=} {--sector= : tic o rrhh}';

    protected $description = 'Crea un usuario administrador (la clave es provisoria: la tiene que cambiar al ingresar)';


    public function handle(): int
    {
        $correo = mb_strtolower(trim($this->argument('email')));

        if (! filter_var($correo, FILTER_VALIDATE_EMAIL)) {
            $this->error('El correo no es válido.');

            return self::FAILURE;
        }

        if (User::withTrashed()->whereRaw('lower(email) = ?', [$correo])->exists()) {
            $this->error('Ya existe un usuario con ese correo.');

            return self::FAILURE;
        }

        $sector = $this->option('sector') ? mb_strtolower(trim($this->option('sector'))) : null;

        if ($sector !== null && ! array_key_exists($sector, User::SECTORES)) {
            $this->error('El sector tiene que ser tic o rrhh.');

            return self::FAILURE;
        }

        $clave = $this->option('password') ?: Str::password(14, symbols: false);

        User::create(['name' => $this->option('nombre'), 'email' => $correo, 'password' => $clave, 'rol' => 'administrador', 'sector' => $sector]);

        $this->info("Administrador creado: {$correo}" . ($sector ? " (sector {$sector})" : ''));
        $this->line("Clave provisoria: {$clave}");
        $this->line('Al ingresar, el sistema le va a pedir que la cambie.');

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\ControlIntentosLogin;
use Illuminate\Console\Command;


// por si queda bloqueado el unico administrador (se corre desde el servidor)
class UsuariosDesbloquear extends Command
{
    protected $signature = 'usuarios:desbloquear {email}';

    protected $description = 'Quita el bloqueo por claves incorrectas de un usuario';


    public function handle(): int
    {
        $usuario = User::whereRaw('lower(email) = ?', [mb_strtolower($this->argument('email'))])->first();

        if (! $usuario) {
            $this->error('No existe ese usuario.');

            return self::FAILURE;
        }

        ControlIntentosLogin::desbloquear($usuario);
        $this->info("Usuario {$usuario->name} desbloqueado.");

        return self::SUCCESS;
    }
}

<?php

namespace App\Console\Commands;

use App\Models\Persona;
use App\Services\HabilitacionUsuarios;
use Illuminate\Console\Command;


class UsuariosSincronizarHabilitacion extends Command
{
    protected $signature = 'usuarios:sincronizar-habilitacion';

    protected $description = 'Deshabilita a los usuarios cuya persona no tiene ningun legajo de alta';


    public function handle(): int
    {
        $antes = \App\Models\User::where('habilitado', false)->count();

        Persona::has('usuario')->with('usuario')->chunkById(200, function ($personas) {
            foreach ($personas as $persona) {
                HabilitacionUsuarios::sincronizar($persona);
            }
        });

        $despues = \App\Models\User::where('habilitado', false)->count();

        $this->info('Usuarios deshabilitados: ' . $despues . ' (antes ' . $antes . ')');

        return self::SUCCESS;
    }
}

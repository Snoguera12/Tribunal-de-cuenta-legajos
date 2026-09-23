<?php

namespace App\Console\Commands;

use App\Models\ApiClient;
use Illuminate\Console\Command;

class CreateApiClientToken extends Command
{
    // El comando recibirá el nombre de la empresa/sistema externo
    protected $signature = 'api:create-client {name}';
    protected $description = 'Crea un cliente externo y genera su Token de acceso permanente';

    public function handle()
    {
        $name = $this->argument('name');

        // 1. Creamos el cliente en la base de datos
        $client = ApiClient::create([
            'name' => $name,
            'is_active' => true
        ]);

        // 2. Le generamos su token de acceso
        $token = $client->createToken('access_token')->plainTextToken;

        $this->info("¡Cliente '{$name}' creado con éxito!");
        $this->line("Token de acceso: <options=bold>{$token}</>");
        $this->warn("Entrega este token al tercero. No se volverá a mostrar por seguridad.");

        return Command::SUCCESS;
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Solo los catalogos (areas, cargos, categorias y oficinas): no crea personas ni usuarios.
     * Para entrar al sistema:   php artisan sistema:crear-admin correo@dominio --sector=tic
     * Para datos de prueba:     ./cargar_datos_prueba.sh   (o php artisan datos:poblar)
     */
    public function run(): void
    {
        $this->call([
            CatalogosSeeder::class,
        ]);
    }
}

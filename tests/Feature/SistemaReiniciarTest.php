<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Auditoria;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;


// sistema:reiniciar deja todo como recien instalado: bases, historico/auditoria y archivos (migrate:fresh dejaba archivos huerfanos)
class SistemaReiniciarTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }


    private function cargarAlgo(): void
    {
        User::create(['name' => 'Admin', 'email' => 'a@test.com', 'password' => 'x12345678', 'rol' => 'administrador']);
        Persona::create(['nombre' => 'Ana', 'apellido' => 'Paz', 'dni' => 30111222, 'cuil' => 27301112228, 'email' => 'ana@test.com',
            'genero' => 0, 'estado_civil' => 0, 'fecha_de_nacimiento' => '1990-01-01']);
        Auditoria::registrar('login', 'algo para borrar');
        Storage::disk('local')->put('documentos/uno.pdf', 'x');
        Storage::disk('local')->put('documentos/dos.pdf', 'y');
    }


    public function test_deja_todo_vacio_y_con_los_catalogos_reales(): void
    {
        $this->cargarAlgo();
        $this->assertGreaterThan(0, Auditoria::count());

        $this->assertSame(0, Artisan::call('sistema:reiniciar', ['--si' => true]));

        $this->assertSame(0, User::count());
        $this->assertSame(0, Persona::withTrashed()->count());
        $this->assertSame(0, Auditoria::count(), 'el historico tambien se vacia');
        $this->assertSame([], Storage::disk('local')->allFiles('documentos'), 'los documentos se borran del disco');
        $this->assertSame(count(\Database\Seeders\CatalogosSeeder::AREAS), Area::count());
    }


    public function test_sin_catalogos_no_los_carga(): void
    {
        $this->cargarAlgo();

        Artisan::call('sistema:reiniciar', ['--si' => true, '--sin-catalogos' => true]);

        $this->assertSame(0, Area::count());
    }


    public function test_en_produccion_no_borra_nada(): void
    {
        $this->cargarAlgo();
        $entorno = $this->app['env'];
        $this->app['env'] = 'production';

        try {
            $this->assertSame(1, Artisan::call('sistema:reiniciar', ['--si' => true]));
        } finally {
            // si no se vuelve al entorno de prueba, al terminar migrate:rollback pediria confirmacion
            $this->app['env'] = $entorno;
        }

        $this->assertSame(1, User::count());
        $this->assertCount(2, Storage::disk('local')->allFiles('documentos'));
    }
}

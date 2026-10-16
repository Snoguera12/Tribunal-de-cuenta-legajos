<?php

namespace Tests\Feature;

use App\Filament\Pages\CopiasDeSeguridad;
use App\Filament\Pages\HistorialDatos;
use App\Filament\Resources\Auditorias\Pages\ListAuditorias;
use App\Filament\Resources\Auditorias\Pages\ViewAuditoria;
use App\Filament\Resources\Incidentes\Pages\EditIncidente;
use App\Filament\Resources\Incidentes\Pages\ListIncidentes;
use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\Persona;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;


class PaginasAdminTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function entrar(string $rol): User
    {
        $usuario = User::create(['name' => $rol, 'email' => "{$rol}@test.com", 'password' => 'x12345678', 'rol' => $rol]);

        Filament::setCurrentPanel('legajos');
        $this->actingAs($usuario);

        return $usuario;
    }


    public function test_el_admin_ve_la_lista_y_el_detalle_de_auditoria(): void
    {
        $this->entrar('administrador');
        Auditoria::registrar('login', 'demo', antes: ['a' => 1], despues: ['a' => 2], extra: ['x' => 'y']);
        $registro = Auditoria::first();

        Livewire::test(ListAuditorias::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Auditoria::all())
            ->filterTable('severidad', ['info'])
            ->assertCanSeeTableRecords(Auditoria::all());

        Livewire::test(ViewAuditoria::class, ['record' => $registro->id])->assertSuccessful();
    }


    public function test_el_admin_ve_y_gestiona_incidentes(): void
    {
        $admin = $this->entrar('administrador');
        $incidente = Incidente::create(['tipo' => 'fuerza_bruta', 'severidad' => 'alta', 'descripcion' => 'prueba', 'ip' => '10.0.0.1']);

        Livewire::test(ListIncidentes::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Incidente::all());

        Livewire::test(EditIncidente::class, ['record' => $incidente->id])
            ->fillForm(['estado' => 'resuelto', 'notas' => 'era una prueba'])
            ->call('save')
            ->assertHasNoFormErrors();

        $incidente->refresh();

        $this->assertEquals('resuelto', $incidente->estado);
        $this->assertEquals($admin->id, $incidente->resuelto_por);
        $this->assertNotNull($incidente->resuelto_en);
        $this->assertEquals(1, Auditoria::where('evento', 'incidente_cerrado')->count());
    }


    public function test_el_admin_ve_el_historial_y_restaura(): void
    {
        $this->entrar('administrador');

        $persona = Persona::create(['nombre' => 'Ana', 'apellido' => 'L', 'dni' => 1, 'cuil' => 2, 'fecha_de_nacimiento' => '1990-01-01']);
        $persona->update(['nombre' => 'Otra']);

        Livewire::test(HistorialDatos::class)
            ->set('tabla', 'personas')
            ->set('registro', (string) $persona->id)
            ->assertSuccessful()
            ->assertSee('UPDATE')
            ->call('restaurar', 1);

        $this->assertEquals('Ana', $persona->fresh()->nombre);
    }


    public function test_el_admin_ve_la_pagina_de_backups(): void
    {
        $this->entrar('administrador');

        Livewire::test(CopiasDeSeguridad::class)->assertSuccessful();
    }


    public function test_los_otros_roles_no_pueden_entrar_a_nada(): void
    {
        foreach (['empleado', 'funcionario', 'rrhh'] as $rol) {

            $this->entrar($rol);

            $this->assertFalse(HistorialDatos::canAccess(), "{$rol} historial");
            $this->assertFalse(CopiasDeSeguridad::canAccess(), "{$rol} backups");

            foreach ([ListAuditorias::class, ListIncidentes::class, HistorialDatos::class, CopiasDeSeguridad::class] as $pagina) {
                try {
                    Livewire::test($pagina)->assertForbidden();
                } catch (HttpException $e) {
                    // algunas paginas cortan con abort(403) antes de renderizar
                    $this->assertEquals(403, $e->getStatusCode(), "{$rol} en {$pagina}");
                }
            }
        }
    }


    public function test_el_admin_descarga_el_informe_de_auditoria_en_csv(): void
    {
        $this->entrar('administrador');

        Auditoria::registrar('login', 'entró', extra: ['dato' => '=HYPERLINK("http://malo")']);
        Auditoria::registrar('modificado', 'otro cambio');

        $componente = Livewire::test(ListAuditorias::class)->callAction('informe');

        $componente->assertFileDownloaded();

        // la descarga queda anotada
        $this->assertEquals(1, Auditoria::where('evento', 'exportacion')->count());
    }


    public function test_el_csv_no_deja_pasar_formulas_de_excel(): void
    {
        $celda = new \ReflectionMethod(\App\Filament\Resources\Incidentes\Pages\ListIncidentes::class, 'celda');
        $celda->setAccessible(true);

        $this->assertSame("'=1+1", $celda->invoke(null, '=1+1'));
        $this->assertSame("'@SUMA(A1)", $celda->invoke(null, '@SUMA(A1)'));
        $this->assertSame("'-2+3", $celda->invoke(null, '-2+3'));
        $this->assertSame('texto normal', $celda->invoke(null, 'texto normal'));
        $this->assertSame('{"a":1}', $celda->invoke(null, ['a' => 1]));
    }


    public function test_el_admin_descarga_el_informe_de_incidentes(): void
    {
        $this->entrar('administrador');
        Incidente::create(['tipo' => 'fuerza_bruta', 'severidad' => 'alta', 'descripcion' => 'prueba']);

        Livewire::test(ListIncidentes::class)->callAction('informe')->assertFileDownloaded();
    }
}

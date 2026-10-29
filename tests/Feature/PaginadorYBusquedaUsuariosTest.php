<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;


// lista de usuarios: buscar por rol, filtros y paginador con paginas vecinas y "ir a la pagina"
class PaginadorYBusquedaUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private User $admin;


    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('legajos');

        $this->admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'x12345678', 'rol' => 'administrador', 'sector' => 'tic']);

        $roles = ['empleado', 'empleado', 'empleado', 'funcionario', 'rrhh'];
        for ($i = 1; $i <= 120; $i++) {
            User::create(['name' => "Usuario {$i}", 'email' => "u{$i}@test.com", 'password' => 'x12345678', 'rol' => $roles[$i % 5]]);
        }

        $this->actingAs($this->admin);
    }


    public function test_se_busca_por_rol_y_no_solo_por_nombre(): void
    {
        $rrhh = User::where('rol', 'rrhh')->get();
        $empleado = User::where('rol', 'empleado')->first();

        Livewire::test(ListUsers::class)
            ->searchTable('rrhh')
            ->assertCanSeeTableRecords($rrhh->take(5))
            ->assertCanNotSeeTableRecords([$empleado]);

        Livewire::test(ListUsers::class)
            ->searchTable('Administrador')
            ->assertCanSeeTableRecords([$this->admin]);

        // el nombre sigue funcionando
        Livewire::test(ListUsers::class)
            ->searchTable('Usuario 7')
            ->assertCanSeeTableRecords([User::where('email', 'u7@test.com')->first()]);
    }


    public function test_se_filtra_por_rol_y_por_sector(): void
    {
        $funcionario = User::where('rol', 'funcionario')->first();
        $empleado = User::where('rol', 'empleado')->first();

        Livewire::test(ListUsers::class)
            ->filterTable('rol', 'funcionario')
            ->assertCanSeeTableRecords([$funcionario])
            ->assertCanNotSeeTableRecords([$empleado, $this->admin]);

        Livewire::test(ListUsers::class)
            ->filterTable('sector', 'tic')
            ->assertCanSeeTableRecords([$this->admin])
            ->assertCanNotSeeTableRecords([$funcionario]);
    }


    // el paginador de Filament mostraba solo la pagina actual: no se podia ir a la de al lado
    public function test_el_paginador_muestra_las_paginas_vecinas(): void
    {
        $html = Livewire::test(ListUsers::class)
            ->set('tableRecordsPerPage', 5)
            ->call('gotoPage', 12)
            ->html();

        foreach ([10, 11, 12, 13, 14] as $pagina) {
            $this->assertStringContainsString("gotoPage({$pagina},", $html, "falta el enlace a la pagina {$pagina}");
        }

        // y siempre quedan las primeras y las ultimas
        $this->assertStringContainsString('gotoPage(1,', $html);
        $ultima = (int) ceil(121 / 5);
        $this->assertStringContainsString("gotoPage({$ultima},", $html);
    }


    public function test_hay_un_cuadro_para_ir_a_una_pagina_y_funciona(): void
    {
        $prueba = Livewire::test(ListUsers::class)->set('tableRecordsPerPage', 5);

        $html = $prueba->html();
        $this->assertStringContainsString('Ir a la página', $html);
        $this->assertStringContainsString('$wire.gotoPage(', $html);

        // lo que hace el boton: ir a la pagina escrita
        $prueba->call('gotoPage', 20, 'page');
        $this->assertSame(20, $prueba->instance()->getTablePage());
    }
}

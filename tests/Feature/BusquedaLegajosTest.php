<?php

namespace Tests\Feature;

use App\Filament\Resources\Legajos\Pages\ListLegajos;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: buscar legajos por número, nombre, apellido o DNI
class BusquedaLegajosTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->usuario('administrador'));
        Filament::setCurrentPanel('legajos');
    }

    public function test_busca_por_dni(): void
    {
        $buscado = $this->crearLegajo(['dni' => 28123456]);
        $otro = $this->crearLegajo(['dni' => 31999888]);

        Livewire::test(ListLegajos::class)
            ->loadTable()
            ->searchTable('28123456')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_busca_por_apellido(): void
    {
        $buscado = $this->crearLegajo(['apellido' => 'Zelarayán']);
        $otro = $this->crearLegajo(['apellido' => 'Gómez']);

        Livewire::test(ListLegajos::class)
            ->loadTable()
            ->searchTable('Zelarayán')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_busca_por_nombre(): void
    {
        $buscado = $this->crearLegajo(['nombre' => 'Hortensia']);
        $otro = $this->crearLegajo(['nombre' => 'Carlos']);

        Livewire::test(ListLegajos::class)
            ->loadTable()
            ->searchTable('Hortensia')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_busca_por_cargo(): void
    {
        $buscado = $this->crearLegajo();
        $buscado->cargo->update(['nombre' => 'Contador Fiscal']);
        $otro = $this->crearLegajo();

        Livewire::test(ListLegajos::class)
            ->loadTable()
            ->searchTable('Contador Fiscal')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }

    public function test_busca_por_numero_de_legajo(): void
    {
        $buscado = $this->crearLegajo([], ['num_legajo' => 45678]);
        $otro = $this->crearLegajo([], ['num_legajo' => 12399]);

        Livewire::test(ListLegajos::class)
            ->loadTable()
            ->searchTable('45678')
            ->assertCanSeeTableRecords([$buscado])
            ->assertCanNotSeeTableRecords([$otro]);
    }
}

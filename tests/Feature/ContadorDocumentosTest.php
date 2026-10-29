<?php

namespace Tests\Feature;

use App\Filament\Resources\Documentos\DocumentoResource;
use App\Filament\Resources\Documentos\Widgets\DocumentosPendientesWidget;
use App\Filament\Resources\Legajos\Widgets\LegajoWidget;
use App\Models\Documento;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;


class ContadorDocumentosTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('legajos');
        $this->actingAs(User::create(['name' => 'RRHH', 'email' => 'rrhh@test.com', 'password' => 'Clave12345', 'rol' => 'rrhh']));
    }


    public function test_el_contador_de_documentos_pendientes_aparece_una_sola_vez_en_el_escritorio(): void
    {
        // el widget de legajos ya no tiene su propio contador de documentos
        Livewire::test(LegajoWidget::class)
            ->assertSee('Total Legajos')
            ->assertDontSee('Documentos Pendientes')
            ->assertDontSee('Documentos pendientes');

        Livewire::test(DocumentosPendientesWidget::class)->assertSee('Documentos pendientes');
    }


    public function test_la_misma_definicion_de_pendiente_en_el_widget_el_menu_y_la_lista(): void
    {
        Documento::create(['ruta' => 'documentos/a.pdf']);                                   // sin nada
        Documento::create(['ruta' => 'documentos/b.pdf', 'descripcion' => 'x', 'tipodoc' => 0]); // sin legajo
        Documento::create(['ruta' => 'documentos/c.pdf', 'descripcion' => 'x', 'legajo_id' => 1]); // sin tipo

        $this->assertEquals(3, Documento::pendientes()->count());
        $this->assertEquals('3', DocumentoResource::getNavigationBadge());

        Livewire::test(DocumentosPendientesWidget::class)->assertSee('3');
    }
}

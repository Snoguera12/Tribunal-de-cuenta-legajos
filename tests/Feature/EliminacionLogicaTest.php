<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Incidente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: ningún registro se borra físicamente
class EliminacionLogicaTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    public function test_un_legajo_no_se_puede_eliminar(): void
    {
        $legajo = $this->crearLegajo();

        $this->assertThrows(fn () => $legajo->delete(), LogicException::class);
        $this->assertDatabaseHas('legajos', ['id' => $legajo->id]);
    }

    public function test_una_persona_no_se_puede_eliminar(): void
    {
        $legajo = $this->crearLegajo();

        $this->assertThrows(fn () => $legajo->persona->delete(), LogicException::class);
        $this->assertDatabaseHas('personas', ['id' => $legajo->persona_id]);
    }

    public function test_un_documento_no_se_puede_eliminar(): void
    {
        $documento = Documento::create(['ruta' => 'documentos/a.pdf', 'nombre_original' => 'a.pdf']);

        $this->assertThrows(fn () => $documento->delete(), LogicException::class);
        $this->assertDatabaseHas('documentos', ['id' => $documento->id]);
    }

    public function test_un_incidente_no_se_puede_eliminar(): void
    {
        $incidente = Incidente::create(['tipo' => 'fuerza_bruta', 'severidad' => 'alta', 'descripcion' => 'prueba']);

        $this->assertThrows(fn () => $incidente->delete(), LogicException::class);
        $this->assertDatabaseHas('incidentes', ['id' => $incidente->id]);
    }

    public function test_ni_el_administrador_puede_eliminar_un_legajo(): void
    {
        $admin = $this->usuario('administrador');
        $legajo = $this->crearLegajo();

        $this->assertFalse($admin->can('delete', $legajo));
    }

    public function test_dar_de_baja_cambia_el_estado_y_conserva_el_registro(): void
    {
        $legajo = $this->crearLegajo();

        $legajo->update(['estado' => \App\Enums\EstadoLegajoEnum::Baja]);

        $this->assertFalse($legajo->fresh()->isAlta());
        $this->assertDatabaseHas('legajos', ['id' => $legajo->id]);
    }
}

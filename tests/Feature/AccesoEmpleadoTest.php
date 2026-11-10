<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: cada empleado ve solo su propia información; el personal de gestión ve todo
class AccesoEmpleadoTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private Legajo $propio;
    private Legajo $ajeno;

    protected function setUp(): void
    {
        parent::setUp();

        $this->propio = $this->crearLegajo();
        $this->ajeno = $this->crearLegajo();
    }

    public function test_el_empleado_solo_ve_su_legajo_y_su_persona(): void
    {
        $this->actingAs($this->usuario('empleado', $this->propio->persona_id));

        $this->assertSame([$this->propio->id], Legajo::pluck('id')->all());
        $this->assertSame([$this->propio->persona_id], Persona::pluck('id')->all());
    }

    public function test_el_empleado_no_puede_abrir_el_legajo_de_otro_por_id(): void
    {
        $this->actingAs($this->usuario('empleado', $this->propio->persona_id));

        $this->assertNull(Legajo::find($this->ajeno->id));
    }

    public function test_un_empleado_sin_persona_vinculada_no_ve_nada(): void
    {
        $this->actingAs($this->usuario('empleado'));

        $this->assertSame(0, Legajo::count());
        $this->assertSame(0, Persona::count());
    }

    public function test_el_empleado_solo_ve_los_documentos_de_su_legajo(): void
    {
        $mio = Documento::create(['ruta' => 'documentos/mio.pdf', 'nombre_original' => 'mio.pdf']);
        $mio->legajos()->attach($this->propio->id);

        $ajeno = Documento::create(['ruta' => 'documentos/ajeno.pdf', 'nombre_original' => 'ajeno.pdf']);
        $ajeno->legajos()->attach($this->ajeno->id);

        $this->actingAs($this->usuario('empleado', $this->propio->persona_id));

        $this->assertSame([$mio->id], Documento::pluck('id')->all());
    }

    public function test_el_personal_de_gestion_ve_todos_los_legajos(): void
    {
        foreach (['funcionario', 'rrhh', 'administrador'] as $rol) {
            $this->actingAs($this->usuario($rol));

            $this->assertSame(2, Legajo::count(), "El rol {$rol} debería ver todos los legajos");
        }
    }
}

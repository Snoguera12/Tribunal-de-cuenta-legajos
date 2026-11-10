<?php

namespace Tests\Feature;

use App\Enums\EstadoCivilEnum;
use App\Enums\GeneroEnum;
use App\Filament\Services\Imports\PersonaImporter;
use App\Services\ProtegeFormulas;
use Tests\TestCase;


class ImportarExportarPersonasTest extends TestCase
{
    public function test_una_celda_que_empieza_como_formula_se_vuelve_texto(): void
    {
        foreach (['=1+1', '@SUMA(A1)', '-2+3', '+cmd|calc'] as $peligrosa) {
            $this->assertSame("'" . $peligrosa, ProtegeFormulas::texto($peligrosa));
        }
    }


    public function test_los_datos_normales_no_se_tocan(): void
    {
        foreach (['Ana Pérez', 'Av. Belgrano 123', '+54 381 555-1234', '', null, 30123456] as $normal) {
            $this->assertSame($normal, ProtegeFormulas::texto($normal));
        }
    }


    public function test_el_importador_entiende_lo_que_exporta_el_exportador(): void
    {
        foreach (GeneroEnum::cases() as $caso) {
            $this->assertSame($caso, PersonaImporter::generoDesde($caso->getLabel()), $caso->name);
        }

        foreach (EstadoCivilEnum::cases() as $caso) {
            $this->assertSame($caso, PersonaImporter::estadoCivilDesde($caso->getLabel()), $caso->name);
        }
    }


    public function test_el_importador_acepta_variantes_y_rechaza_lo_desconocido(): void
    {
        $this->assertSame(GeneroEnum::Femenino, PersonaImporter::generoDesde('  F '));
        $this->assertSame(GeneroEnum::Masculino, PersonaImporter::generoDesde('MASCULINO'));
        $this->assertSame(EstadoCivilEnum::Casado, PersonaImporter::estadoCivilDesde('Casada'));
        $this->assertNull(PersonaImporter::generoDesde('marciano'));
        $this->assertNull(PersonaImporter::estadoCivilDesde(''));
    }


    public function test_la_columna_del_telefono_de_emergencia_coincide_con_la_base(): void
    {
        $columnas = collect(PersonaImporter::getColumns())->map(fn ($c) => $c->getName());

        $this->assertTrue($columnas->contains('telefono_emergencia'));
        $this->assertFalse($columnas->contains('teléfono_emergencia'));
    }

    public function test_la_fecha_se_convierte_y_las_invalidas_quedan_para_que_las_rechace_la_regla(): void
    {
        $this->assertSame('1985-03-07', PersonaImporter::fechaDesde('07/03/1985'));
        $this->assertSame('31/02/1990', PersonaImporter::fechaDesde('31/02/1990'));
        $this->assertSame('hola', PersonaImporter::fechaDesde('hola'));
        $this->assertNull(PersonaImporter::fechaDesde(''));
    }
}

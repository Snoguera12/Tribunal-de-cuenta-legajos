<?php

namespace Tests\Feature;

use App\Enums\IdiomaNivelEnum;
use App\Enums\NivelEstudioEnum;
use App\Models\Estudio;
use App\Models\EstudioTitulo;
use App\Models\Idioma;
use App\Models\Institucion;
use App\Models\PersonaIdioma;
use App\Models\Titulo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LogicException;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: ni al fusionar ni al descartar un catálogo se borra nada
class FusionCatalogosTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private function estudio(int $personaId): Estudio
    {
        return Estudio::create(['nivel_estudio' => NivelEstudioEnum::Primario->value, 'persona_id' => $personaId]);
    }

    public function test_fusionar_un_titulo_mueve_los_estudios_y_conserva_el_registro(): void
    {
        $origen = Titulo::create(['nombre' => 'Ing.']);
        $destino = Titulo::create(['nombre' => 'Ingeniero']);
        $estudio = $this->estudio($this->crearLegajo()->persona_id);
        $registro = EstudioTitulo::create(['estudio_id' => $estudio->id, 'titulo_id' => $origen->id]);

        $origen->fusionarEn($destino);

        $this->assertSame($destino->id, $registro->fresh()->titulo_id);
        // sigue en la base, marcado, pero ya no se lista ni se puede elegir
        $this->assertDatabaseHas('titulos', ['id' => $origen->id, 'fusionado_en_id' => $destino->id]);
        $this->assertNull(Titulo::find($origen->id));
        $this->assertSame([$destino->id], Titulo::pluck('id')->all());
        $this->assertNotNull(Titulo::withoutGlobalScopes()->find($origen->id)->fusionado_en);
    }

    public function test_el_nombre_de_un_titulo_fusionado_se_puede_volver_a_usar(): void
    {
        $origen = Titulo::create(['nombre' => 'Ing.']);
        $destino = Titulo::create(['nombre' => 'Ingeniero']);

        $origen->fusionarEn($destino);

        $nuevo = Titulo::create(['nombre' => 'Ing.']);

        $this->assertNotSame($origen->id, $nuevo->id);
    }

    public function test_si_el_estudio_ya_tenia_el_titulo_destino_el_repetido_se_descarta_sin_borrarse(): void
    {
        $origen = Titulo::create(['nombre' => 'Ing.']);
        $destino = Titulo::create(['nombre' => 'Ingeniero']);
        $estudio = $this->estudio($this->crearLegajo()->persona_id);

        $propio = EstudioTitulo::create(['estudio_id' => $estudio->id, 'titulo_id' => $destino->id]);
        $repetido = EstudioTitulo::create(['estudio_id' => $estudio->id, 'titulo_id' => $origen->id]);

        $origen->fusionarEn($destino);

        $this->assertSame([$propio->id], $estudio->estudioTitulos()->pluck('id')->all());
        $this->assertDatabaseHas('estudio_titulos', ['id' => $repetido->id]);
        $this->assertNotNull(EstudioTitulo::withoutGlobalScopes()->find($repetido->id)->descartado_en);
    }

    public function test_asignar_pendientes_que_ya_estaban_descarta_el_repetido(): void
    {
        $titulo = Titulo::create(['nombre' => 'Contador']);
        $estudio = $this->estudio($this->crearLegajo()->persona_id);

        EstudioTitulo::create(['estudio_id' => $estudio->id, 'titulo_id' => $titulo->id]);
        $pendiente = EstudioTitulo::create(['estudio_id' => $estudio->id, 'titulo_otro' => 'Contadora']);

        EstudioTitulo::asignarATitulo(collect([$pendiente]), $titulo->id);

        $this->assertDatabaseHas('estudio_titulos', ['id' => $pendiente->id]);
        $this->assertSame(0, EstudioTitulo::pendientes()->count());
        $this->assertSame(1, $estudio->estudioTitulos()->count());
    }

    public function test_fusionar_una_institucion_mueve_estudios_y_cursos_y_conserva_el_registro(): void
    {
        $origen = Institucion::create(['nombre' => 'UNSE']);
        $destino = Institucion::create(['nombre' => 'Universidad Nacional de Santiago del Estero']);
        $estudio = $this->estudio($this->crearLegajo()->persona_id);
        $estudio->update(['institucion_id' => $origen->id]);

        $origen->fusionarEn($destino);

        $this->assertSame($destino->id, $estudio->fresh()->institucion_id);
        $this->assertDatabaseHas('institucions', ['id' => $origen->id, 'fusionado_en_id' => $destino->id]);
        $this->assertNull(Institucion::find($origen->id));
    }

    public function test_la_lista_de_instituciones_pendientes_funciona_con_el_filtro_de_vigentes(): void
    {
        $estudio = $this->estudio($this->crearLegajo()->persona_id);
        $estudio->update(['institucion_otro' => 'Escuela Nueva']);

        $this->assertSame(1, Institucion::consultaPendientes()->count());
    }

    public function test_fusionar_un_idioma_mueve_los_registros_y_conserva_el_idioma(): void
    {
        $origen = Idioma::create(['nombre' => 'Ingles']);
        $destino = Idioma::create(['nombre' => 'Inglés']);
        $persona = $this->crearLegajo()->persona;
        $registro = PersonaIdioma::create(['persona_id' => $persona->id, 'idioma_id' => $origen->id, 'nivel' => IdiomaNivelEnum::Basico]);

        $origen->fusionarEn($destino);

        $this->assertSame($destino->id, $registro->fresh()->idioma_id);
        $this->assertDatabaseHas('idiomas', ['id' => $origen->id, 'fusionado_en_id' => $destino->id]);
        $this->assertNull(Idioma::find($origen->id));
    }

    public function test_si_la_persona_ya_tenia_el_idioma_destino_el_repetido_se_desadjunta_sin_borrarse(): void
    {
        $origen = Idioma::create(['nombre' => 'Ingles']);
        $destino = Idioma::create(['nombre' => 'Inglés']);
        $persona = $this->crearLegajo()->persona;

        PersonaIdioma::create(['persona_id' => $persona->id, 'idioma_id' => $destino->id, 'nivel' => IdiomaNivelEnum::Avanzado]);
        $repetido = PersonaIdioma::create(['persona_id' => $persona->id, 'idioma_id' => $origen->id, 'nivel' => IdiomaNivelEnum::Basico]);

        $origen->fusionarEn($destino);

        $this->assertDatabaseHas('persona_idiomas', ['id' => $repetido->id, 'persona_id' => null]);
    }

    public function test_los_catalogos_no_se_pueden_eliminar(): void
    {
        $titulo = Titulo::create(['nombre' => 'Perito']);
        $institucion = Institucion::create(['nombre' => 'Escuela 1']);
        $idioma = Idioma::create(['nombre' => 'Francés']);
        $usuario = User::factory()->create();

        foreach ([$titulo, $institucion, $idioma, $usuario] as $registro) {
            $this->assertThrows(fn () => $registro->delete(), LogicException::class);
        }
    }

    public function test_los_registros_de_estudios_e_idiomas_de_una_persona_no_se_pueden_eliminar(): void
    {
        $persona = $this->crearLegajo()->persona;
        $titulo = Titulo::create(['nombre' => 'Perito']);
        $idioma = Idioma::create(['nombre' => 'Francés']);
        $estudio = $this->estudio($persona->id);

        $estudioTitulo = EstudioTitulo::create(['estudio_id' => $estudio->id, 'titulo_id' => $titulo->id]);
        $personaIdioma = PersonaIdioma::create(['persona_id' => $persona->id, 'idioma_id' => $idioma->id, 'nivel' => IdiomaNivelEnum::Basico]);

        $this->assertThrows(fn () => $estudioTitulo->delete(), LogicException::class);
        $this->assertThrows(fn () => $personaIdioma->delete(), LogicException::class);
        $this->assertThrows(fn () => $estudio->delete(), LogicException::class);
    }
}

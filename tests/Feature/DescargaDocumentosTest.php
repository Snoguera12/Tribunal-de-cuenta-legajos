<?php

namespace Tests\Feature;

use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Models\Area;
use App\Models\Auditoria;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use App\Services\ArchivoSeguro;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;


// abrir en una pestaña y descargar el documento: quien puede, como queda anotado, y la opcion para el empleado
class DescargaDocumentosTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private string $ruta = 'documentos/prueba.pdf';
    private string $contenido = '%PDF-1.4 hola mundo %%EOF';
    private int $n = 0;
    private Documento $documento;
    private Legajo $legajoDelEmpleado;


    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put($this->ruta, ArchivoSeguro::encriptar($this->contenido));

        $this->legajoDelEmpleado = $this->legajo();
        $this->documento = Documento::create([
            'ruta' => $this->ruta, 'descripcion' => 'DNI', 'tipodoc' => 0,
            'nombre_original' => 'Resolución N° 5/2026.pdf', 'legajo_id' => $this->legajoDelEmpleado->id,
        ]);
    }


    private function legajo(): Legajo
    {
        $this->n++;
        $persona = Persona::create(['nombre' => "P{$this->n}", 'apellido' => 'Test', 'dni' => 30000000 + $this->n, 'cuil' => 20000000000 + $this->n,
            'email' => "p{$this->n}@test.com", 'genero' => 0, 'estado_civil' => 0, 'fecha_de_nacimiento' => '1990-01-01']);

        return Legajo::create(['num_legajo' => 1000 + $this->n, 'estado' => true, 'fecha_de_ingreso' => '2020-01-01', 'tipo_contrato' => 2, 'persona_id' => $persona->id,
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'CAT 1'], ['descripcion' => 'x'])->id,
            'cargo_id' => Cargo::firstOrCreate(['nombre' => 'Administrativo'])->id,
            'area_id' => Area::firstOrCreate(['nombre' => 'Personal'])->id]);
    }


    private function usuario(string $rol, ?int $personaId = null): User
    {
        $this->n++;

        return User::create(['name' => $rol, 'email' => "{$rol}{$this->n}@test.com", 'password' => 'x12345678', 'rol' => $rol, 'persona_id' => $personaId]);
    }


    public function test_abrir_se_muestra_en_el_navegador_y_descargar_se_guarda_con_nombre(): void
    {
        $funcionario = $this->usuario('funcionario');

        $ver = $this->actingAs($funcionario)->get($this->documento->urlTemporal());
        $ver->assertOk();
        $this->assertStringStartsWith('inline;', $ver->headers->get('Content-Disposition'));

        $bajar = $this->actingAs($funcionario)->get($this->documento->urlTemporal(true));
        $bajar->assertOk();
        $this->assertSame($this->contenido, $bajar->getContent());
        $this->assertStringStartsWith('attachment;', $bajar->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Resolucion_N_5_2026.pdf', $bajar->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $bajar->headers->get('Cache-Control'));

        // cada cosa queda anotada por separado
        $this->assertSame(1, Auditoria::where('evento', 'ver_documento')->count());
        $this->assertSame(1, Auditoria::where('evento', 'descargar_documento')->count());
    }


    public function test_sin_nombre_original_se_arma_uno_con_la_extension(): void
    {
        $this->documento->update(['nombre_original' => null]);

        $this->assertSame('documento-' . $this->documento->id . '.pdf', $this->documento->fresh()->nombreDescarga());

        // un nombre con caracteres raros o intentos de salir de la carpeta queda limpio
        $this->documento->update(['nombre_original' => '../../etc/pass wd?.pdf']);
        $this->assertSame('etc_pass_wd_.pdf', $this->documento->fresh()->nombreDescarga());
    }


    public function test_el_enlace_de_descarga_esta_firmado_y_no_se_puede_cambiar(): void
    {
        $enlaceVer = $this->documento->urlTemporal();
        $enlaceBajar = $this->documento->urlTemporal(true);

        // sin sesion no se abre ni con el enlace bueno
        $this->get($enlaceBajar)->assertUnauthorized();

        // sacar o agregar descargar=1 a mano rompe la firma
        $rrhh = $this->usuario('rrhh');
        $this->actingAs($rrhh)->get($enlaceVer . '&descargar=1')->assertForbidden();
        $this->actingAs($rrhh)->get(str_replace('descargar=1', 'descargar=0', $enlaceBajar))->assertForbidden();
    }


    public function test_por_defecto_el_empleado_no_abre_ni_descarga_ni_los_suyos(): void
    {
        $empleado = $this->usuario('empleado', $this->legajoDelEmpleado->persona_id);

        $this->actingAs($empleado)->get($this->documento->urlTemporal())->assertForbidden();
        $this->actingAs($empleado)->get($this->documento->urlTemporal(true))->assertForbidden();

        $this->assertSame(2, Auditoria::where('evento', 'acceso_denegado')->count());
        $this->assertSame(0, Auditoria::where('evento', 'descargar_documento')->count());
    }


    public function test_con_la_opcion_activada_el_empleado_abre_y_descarga_solo_los_suyos(): void
    {
        config(['sistema.documentos.empleado_abre_propios' => true]);

        $suyo = $this->usuario('empleado', $this->legajoDelEmpleado->persona_id);
        $ajeno = $this->usuario('empleado', $this->legajo()->persona_id);

        $this->actingAs($suyo)->get($this->documento->urlTemporal())->assertOk();
        $bajar = $this->actingAs($suyo)->get($this->documento->urlTemporal(true));
        $bajar->assertOk();
        $this->assertSame($this->contenido, $bajar->getContent());

        // el de otra persona sigue prohibido, aunque tenga el enlace
        $this->actingAs($ajeno)->get($this->documento->urlTemporal(true))->assertForbidden();

        // tambien lo puede abrir si el documento se compartio con su legajo
        $ajeno2 = $this->legajo();
        $this->documento->asignarA([$this->legajoDelEmpleado->id, $ajeno2->id]);
        $conCopia = $this->usuario('empleado', $ajeno2->persona_id);
        $this->actingAs($conCopia)->get($this->documento->fresh()->urlTemporal(true))->assertOk();
    }


    public function test_la_lista_muestra_el_boton_descargar_solo_a_quien_puede(): void
    {
        Filament::setCurrentPanel('legajos');

        $this->actingAs($this->usuario('funcionario'));
        Livewire::test(ListDocumentos::class)
            ->filterTable('estado', 'todos')
            ->assertTableColumnStateSet('descarga', 'Descargar', $this->documento)
            ->assertTableColumnFormattedStateSet('ruta', 'Abrir archivo', $this->documento);

        $empleado = $this->usuario('empleado', $this->legajoDelEmpleado->persona_id);
        $this->actingAs($empleado);
        Livewire::test(ListDocumentos::class)
            ->filterTable('estado', 'todos')
            ->assertTableColumnStateSet('descarga', null, $this->documento);
    }
}

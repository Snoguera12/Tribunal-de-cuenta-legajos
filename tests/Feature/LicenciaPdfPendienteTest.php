<?php

namespace Tests\Feature;

use App\Enums\TipodocEnum;
use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Models\ApiClient;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use App\Services\MiniPdf;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;


// el PDF de licencia que manda el otro sistema queda pendiente y RRHH lo asigna a uno, varios o todos sin repetirlo
class LicenciaPdfPendienteTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private int $n = 0;


    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }


    private function usuario(string $rol, ?int $personaId = null): User
    {
        $this->n++;

        return User::create(['name' => $rol, 'email' => "{$rol}{$this->n}@test.com", 'password' => 'x12345678', 'rol' => $rol, 'persona_id' => $personaId]);
    }


    private function legajo(): Legajo
    {
        $this->n++;

        $persona = Persona::create(['nombre' => "P{$this->n}", 'apellido' => 'Test', 'dni' => 30000000 + $this->n, 'cuil' => 20000000000 + $this->n,
            'email' => "p{$this->n}@test.com", 'genero' => 0, 'estado_civil' => 0, 'fecha_de_nacimiento' => '1990-01-01']);

        return Legajo::create(['num_legajo' => 1000 + $this->n, 'estado' => true, 'fecha_de_ingreso' => '2020-01-01', 'tipo_contrato' => 2,
            'persona_id' => $persona->id,
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'CAT 1'], ['descripcion' => 'Secretario General'])->id,
            'cargo_id' => Cargo::firstOrCreate(['nombre' => 'Administrativo'])->id,
            'area_id' => Area::firstOrCreate(['nombre' => 'Personal'])->id]);
    }


    private function pdf(string $texto = 'Licencia'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('licencia.pdf', MiniPdf::hoja($texto, [['Resolución de licencia aprobada', 10, false]]));
    }


    private ?string $token = null;


    // cliente de la api con su token, igual que el sistema de licencias real
    private function cliente(): ApiClient
    {
        $cliente = ApiClient::create(['nombre' => 'Sistema de Licencias', 'is_active' => true, 'is_token' => true]);
        $this->token = $cliente->createToken('licencias')->plainTextToken;

        return $cliente;
    }


    private function recibir(array $extra = [], ?UploadedFile $archivo = null)
    {
        return $this->withToken($this->token)
            ->post('/api/licencias/documentos', ['archivo' => $archivo ?? $this->pdf(), ...$extra], ['Accept' => 'application/json']);
    }


    // ---------------------------------------------------------- api

    public function test_sin_token_no_se_puede_mandar_el_pdf(): void
    {
        $this->post('/api/licencias/documentos', ['archivo' => $this->pdf()], ['Accept' => 'application/json'])->assertStatus(401);

        $this->assertSame(0, Documento::count());
    }


    public function test_un_cliente_inactivo_no_puede_mandar_el_pdf(): void
    {
        $cliente = $this->cliente();
        $cliente->update(['is_active' => false]);

        $this->recibir()->assertStatus(403);
        $this->assertSame(0, Documento::count());
    }


    // los endpoints que ya existian tambien se prueban con un pedido real (token + limite de pedidos)
    public function test_la_api_responde_con_un_token_real_y_no_da_error_500(): void
    {
        $this->cliente();
        $legajo = $this->legajo();

        $this->withToken($this->token)->getJson('/api/personas')->assertOk();
        $this->withToken($this->token)->getJson('/api/legajos/por-dni/' . $legajo->persona->dni)->assertOk();
    }


    public function test_el_pdf_queda_pendiente_cifrado_y_sin_legajo(): void
    {
        $this->cliente();

        $r = $this->recibir(['id_solicitud' => '5521', 'nro_resolucion' => '88/2026']);

        $r->assertStatus(201)->assertJson(['ok' => true, 'pendiente' => true, 'id_solicitud' => '5521']);

        $doc = Documento::first();
        $this->assertNull($doc->legajo_id);
        $this->assertSame(TipodocEnum::Licencia, $doc->tipodoc);
        $this->assertSame('api_licencias', $doc->origen);
        $this->assertStringContainsString('88/2026', $doc->descripcion);
        $this->assertTrue(Documento::pendientes()->whereKey($doc->id)->exists());

        // en el disco esta cifrado: no se lee como PDF
        $this->assertStringNotContainsString('%PDF', Storage::disk('local')->get($doc->ruta));
    }


    public function test_el_reintento_con_el_mismo_numero_no_duplica(): void
    {
        $this->cliente();

        $this->recibir(['id_solicitud' => '77'])->assertStatus(201);
        $this->recibir(['id_solicitud' => '77'])->assertStatus(200)->assertJson(['ok' => true]);

        $this->assertSame(1, Documento::count());
    }


    public function test_el_mismo_pdf_con_otro_numero_tampoco_se_repite(): void
    {
        $this->cliente();

        $this->recibir(['id_solicitud' => '1'], $this->pdf('Igual'))->assertStatus(201);
        $this->recibir(['id_solicitud' => '2'], $this->pdf('Igual'))->assertStatus(200);

        $this->assertSame(1, Documento::count());
        $this->assertCount(1, Storage::disk('local')->allFiles('documentos'));
    }


    public function test_un_archivo_que_no_es_pdf_se_rechaza(): void
    {
        $this->cliente();

        $this->recibir([], UploadedFile::fake()->createWithContent('malo.pdf', '<?php echo 1; ?>'))->assertStatus(422);
        $this->recibir([], UploadedFile::fake()->createWithContent('foto.png', 'no soy un pdf'))->assertStatus(422);
        $this->withToken($this->token)->post('/api/licencias/documentos', [], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertSame(0, Documento::count());
    }


    // ---------------------------------------------------------- asignacion

    private function pendiente(): Documento
    {
        $this->cliente();
        $this->recibir(['id_solicitud' => (string) random_int(1, 999999)])->assertStatus(201);

        // despues del pedido de la api se vuelve a la sesion normal del panel
        $this->flushHeaders();
        Auth::forgetGuards();
        Auth::shouldUse('web');

        return Documento::latest('id')->first();
    }


    public function test_asignar_a_una_persona(): void
    {
        $doc = $this->pendiente();
        $legajo = $this->legajo();

        $doc->asignarA([$legajo->id]);

        $doc->refresh();
        $this->assertSame($legajo->id, $doc->legajo_id);
        $this->assertSame([$legajo->id], $doc->legajos()->pluck('legajos.id')->all());
        $this->assertFalse(Documento::pendientes()->whereKey($doc->id)->exists());
    }


    public function test_un_solo_documento_para_varios_o_todos_sin_copias(): void
    {
        $doc = $this->pendiente();
        $legajos = collect([$this->legajo(), $this->legajo(), $this->legajo()]);

        $doc->asignarA($legajos->pluck('id')->all());

        $this->assertSame(1, Documento::count());
        $this->assertSame(3, $doc->legajos()->count());
        $this->assertCount(1, Storage::disk('local')->allFiles('documentos'));

        // asignar de nuevo a los mismos no repite filas
        $doc->asignarA($legajos->pluck('id')->all());
        $this->assertSame(3, $doc->legajos()->count());

        // cada legajo lo ve entre sus documentos
        foreach ($legajos as $legajo) {
            $this->assertTrue($legajo->documentosTodos()->whereKey($doc->id)->exists());
            $this->assertTrue(Documento::delLegajo($legajo->id)->whereKey($doc->id)->exists());
        }

        // sacar a uno
        $doc->asignarA($legajos->take(2)->pluck('id')->all());
        $this->assertSame(2, $doc->legajos()->count());

        // sin nadie vuelve a pendientes
        $doc->asignarA([]);
        $doc->refresh();
        $this->assertNull($doc->legajo_id);
        $this->assertTrue(Documento::pendientes()->whereKey($doc->id)->exists());
    }


    public function test_el_empleado_ve_solo_lo_que_le_asignaron(): void
    {
        $doc = $this->pendiente();
        $mio = $this->legajo();
        $ajeno = $this->legajo();
        $empleado = $this->usuario('empleado', $mio->persona_id);
        $otro = $this->usuario('empleado', $ajeno->persona_id);

        $doc->asignarA([$mio->id]);

        $this->assertTrue($empleado->can('view', $doc));
        $this->assertFalse($otro->can('view', $doc));

        $this->assertTrue(Documento::deLaPersona($mio->persona_id)->whereKey($doc->id)->exists());
        $this->assertFalse(Documento::deLaPersona($ajeno->persona_id)->whereKey($doc->id)->exists());

        // compartido con el otro: ahora lo ven los dos (sigue siendo un solo documento)
        $doc->asignarA([$mio->id, $ajeno->id]);
        $this->assertTrue($otro->fresh()->can('view', $doc->fresh()));
    }


    public function test_un_documento_cargado_con_legajo_queda_en_las_asignaciones(): void
    {
        $legajo = $this->legajo();

        $doc = Documento::create(['ruta' => 'documentos/x.pdf', 'descripcion' => 'DNI', 'tipodoc' => 0, 'legajo_id' => $legajo->id]);

        $this->assertSame([$legajo->id], $doc->legajos()->pluck('legajos.id')->all());
    }


    public function test_rrhh_asigna_desde_la_lista_y_el_funcionario_no_puede(): void
    {
        Filament::setCurrentPanel('legajos');

        $doc = $this->pendiente();
        $a = $this->legajo();
        $b = $this->legajo();

        // funcionario: no ve la accion
        $this->actingAs($this->usuario('funcionario'));
        Livewire::test(ListDocumentos::class)->assertTableActionHidden('asignar', $doc);

        // rrhh: a varios
        $this->actingAs($this->usuario('rrhh'));
        Livewire::test(ListDocumentos::class)
            ->assertTableActionVisible('asignar', $doc)
            ->callTableAction('asignar', $doc, data: [
                'tipodoc' => TipodocEnum::Licencia->value,
                'descripcion' => 'Licencia por examen',
                'destino' => 'varios',
                'legajos' => [$a->id, $b->id],
            ])
            ->assertHasNoTableActionErrors();

        $doc->refresh();
        $this->assertSame(2, $doc->legajos()->count());
        $this->assertSame('Licencia por examen', $doc->descripcion);
        $this->assertFalse(Documento::pendientes()->whereKey($doc->id)->exists());
    }


    public function test_asignar_a_todos_los_legajos_de_alta(): void
    {
        Filament::setCurrentPanel('legajos');

        $doc = $this->pendiente();
        $alta1 = $this->legajo();
        $alta2 = $this->legajo();
        $baja = $this->legajo();
        $baja->update(['estado' => false]);

        $this->actingAs($this->usuario('administrador'));
        Livewire::test(ListDocumentos::class)
            ->callTableAction('asignar', $doc, data: [
                'tipodoc' => TipodocEnum::Licencia->value,
                'descripcion' => 'Circular general',
                'destino' => 'todos',
            ])
            ->assertHasNoTableActionErrors();

        $ids = $doc->fresh()->legajos()->pluck('legajos.id')->sort()->values()->all();
        $this->assertSame(collect([$alta1->id, $alta2->id])->sort()->values()->all(), $ids);
        $this->assertSame(1, Documento::count());
    }
}

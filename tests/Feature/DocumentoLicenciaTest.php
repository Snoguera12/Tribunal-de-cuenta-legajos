<?php

namespace Tests\Feature;

use App\Enums\EstadoLegajoEnum;
use App\Enums\TipodocEnum;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Token;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DocumentoLicenciaTest extends TestCase
{
    use RefreshDatabase;

    private const ENDPOINT = '/api/licencias/documentos';

    private string $plainToken;
    private Token $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        $this->cliente = Token::create(['nombre' => 'Sistema de licencias', 'is_active' => true, 'is_created' => true]);
        $this->plainToken = $this->cliente->createToken('licencias')->plainTextToken;
    }

    private function legajoDeAlta(string $dni = '30111222'): Legajo
    {
        $persona = Persona::create([
            'nombre' => 'Ana', 'apellido' => 'Pérez', 'dni' => (int) $dni, 'cuil' => 20301112229,
            'email' => 'ana@example.com', 'genero' => 1, 'estado_civil' => 1,
            'fecha_de_nacimiento' => '1990-01-01',
        ]);

        return Legajo::create([
            'num_legajo' => random_int(1000, 99999),
            'estado' => EstadoLegajoEnum::Alta,
            'fecha_de_ingreso' => now(),
            'persona_id' => $persona->id,
            'categoria_id' => Categoria::create(['nombre' => 'CAT 1', 'descripcion' => 'Test'])->id,
            'cargo_id' => Cargo::create(['nombre' => 'Cargo test'])->id,
            'area_id' => Area::create(['nombre' => 'Área test'])->id,
        ]);
    }

    // PDF con contenido real: el contenido depende del tamaño pedido, así que dos archivos de distinto tamaño tienen hash distinto
    private function pdf(string $nombre = 'licencia.pdf', int $kb = 100): UploadedFile
    {
        $contenido = '%PDF-1.4 ' . str_repeat('x', $kb * 1024 - 9);

        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    private function enviar(array $datos = [], ?UploadedFile $archivo = null)
    {
        return $this->withHeader('Authorization', 'Bearer ' . $this->plainToken)
            ->postJson(self::ENDPOINT, array_merge([
                'archivo' => $archivo ?? $this->pdf(),
                'id_solicitud' => 501,
                'dni' => '30111222',
                'nro_resolucion' => '123/2026',
                'descripcion' => 'Licencia por enfermedad',
            ], $datos));
    }

    public function test_recibe_el_pdf_y_lo_guarda_en_el_legajo_de_alta(): void
    {
        $legajo = $this->legajoDeAlta();

        $respuesta = $this->enviar();

        $respuesta->assertCreated()
            ->assertJsonPath('legajo_id', $legajo->id)
            ->assertJsonPath('id_solicitud', 501);

        $documento = Documento::findOrFail($respuesta->json('documento_id'));
        $this->assertEquals(TipodocEnum::Licencia, $documento->tipodoc);
        $this->assertEquals($this->cliente->id, $documento->token_id);
        $this->assertEquals('licencia.pdf', $documento->nombre_original);
        $this->assertTrue($documento->legajos->contains($legajo));
        Storage::disk('local')->assertExists($documento->ruta);
    }

    public function test_reenvio_identico_responde_200_y_no_duplica(): void
    {
        $legajo = $this->legajoDeAlta();
        $archivo = $this->pdf();
        $this->enviar([], $archivo)->assertCreated();

        $respuesta = $this->enviar([], $this->pdf('licencia.pdf', 100));

        $respuesta->assertOk();
        $this->assertEquals(1, Documento::count());
        $this->assertEquals(1, $legajo->fresh()->documentos()->count());
    }

    public function test_mismo_id_solicitud_con_otro_archivo_responde_409(): void
    {
        $this->legajoDeAlta();
        $this->enviar([], $this->pdf('a.pdf', 100))->assertCreated();

        $this->enviar([], $this->pdf('b.pdf', 120))->assertStatus(409);
        $this->assertEquals(1, Documento::count());
    }

    public function test_rechaza_archivos_que_no_son_pdf(): void
    {
        $this->legajoDeAlta();
        $imagen = UploadedFile::fake()->image('foto.jpg');

        $this->enviar([], $imagen)->assertStatus(422)->assertJsonValidationErrors(['archivo']);
        $this->assertEquals(0, Documento::count());
    }

    public function test_rechaza_archivos_de_mas_de_10_mb(): void
    {
        $this->legajoDeAlta();

        $this->enviar([], $this->pdf('grande.pdf', 10241))->assertStatus(422)->assertJsonValidationErrors(['archivo']);
    }

    public function test_acepta_el_pdf_de_10_mb_exactos(): void
    {
        $this->legajoDeAlta();

        $this->enviar([], $this->pdf('justo.pdf', 10240))->assertCreated();
    }

    public function test_dni_sin_persona_responde_422(): void
    {
        $this->enviar(['dni' => '99999999'])->assertStatus(422)->assertJsonValidationErrors(['dni']);
    }

    public function test_persona_sin_legajo_de_alta_responde_422(): void
    {
        $legajo = $this->legajoDeAlta();
        $legajo->update(['estado' => EstadoLegajoEnum::Baja]);

        $this->enviar()->assertStatus(422);
        $this->assertEquals(0, Documento::count());
    }

    public function test_dni_con_puntos_se_normaliza(): void
    {
        $this->legajoDeAlta();

        $this->enviar(['dni' => '30.111.222'])->assertCreated();
    }

    public function test_sin_token_responde_401(): void
    {
        $this->legajoDeAlta();

        $this->postJson(self::ENDPOINT, ['archivo' => $this->pdf(), 'id_solicitud' => 1, 'dni' => '30111222'])
            ->assertUnauthorized();
    }

    public function test_cliente_inactivo_responde_403(): void
    {
        $this->legajoDeAlta();
        $this->cliente->update(['is_active' => false]);

        $this->enviar()->assertForbidden();
    }
}

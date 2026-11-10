<?php

namespace Tests\Feature;

use App\Enums\TipodocEnum;
use App\Models\Auditoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Licencia;
use App\Models\Token;
use App\Services\ArchivoSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// API: el sistema de licencias registra una licencia aprobada con su PDF en el legajo
class LicenciaApiTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private Token $cliente;
    private string $token;
    private Legajo $legajo;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'app.key' => 'base64:' . base64_encode(random_bytes(32)),
            'sistema.documentos.optimizar' => false,
            'sistema.documentos.cifrar' => true,
        ]);

        $this->cliente = Token::create(['nombre' => 'Sistema de licencias', 'is_active' => true, 'is_created' => true]);
        $this->token = $this->cliente->createToken('licencias')->plainTextToken;
        $this->legajo = $this->crearLegajo();
    }

    private function pdf(string $nombre = 'licencia.pdf', string $extra = 'licencia de prueba'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, $this->pdfDePrueba($extra));
    }

    private function enviar(array $datos = [], ?Legajo $legajo = null, bool $conToken = true)
    {
        $url = '/api/legajos/' . ($legajo ?? $this->legajo)->id . '/licencias';
        $cuerpo = array_merge([
            'archivo' => $this->pdf(),
            'solicitud_id' => 501,
            'tipo_licencia' => 'Licencia por enfermedad',
            'fecha_inicio' => '2026-09-01',
            'fecha_fin' => '2026-09-05',
            'dias_solicitados' => 5,
            'fecha_aprobacion' => '2026-08-30',
            'nro_resolucion' => '123/2026',
            'motivo' => 'Reposo médico',
        ], $datos);

        return $conToken
            ? $this->withHeader('Authorization', 'Bearer ' . $this->token)->postJson($url, $cuerpo)
            : $this->postJson($url, $cuerpo);
    }

    public function test_registra_la_licencia_y_guarda_el_pdf_cifrado_en_el_legajo(): void
    {
        $respuesta = $this->enviar();

        $respuesta->assertCreated()->assertJsonPath('ok', true)->assertJsonPath('id_solicitud', 501);

        $documento = Documento::findOrFail($respuesta->json('documento_id'));
        $this->assertEquals(TipodocEnum::Licencia, $documento->tipodoc);
        $this->assertSame('licencia.pdf', $documento->nombre_original);
        $this->assertTrue($documento->legajos->contains($this->legajo));
        $this->assertSame(1, Licencia::count());

        // el PDF queda cifrado en el disco y se puede volver a leer completo
        Storage::disk('local')->assertExists($documento->ruta);
        $this->assertStringNotContainsString('%PDF-', Storage::disk('local')->get($documento->ruta));
        $this->assertSame($this->pdfDePrueba('licencia de prueba'), ArchivoSeguro::leer($documento->ruta));
    }

    public function test_un_reenvio_identico_responde_200_y_no_duplica(): void
    {
        $this->enviar()->assertCreated();

        $this->enviar()->assertOk();

        $this->assertSame(1, Licencia::count());
        $this->assertSame(1, Documento::count());
    }

    public function test_el_mismo_solicitud_id_con_datos_distintos_responde_409(): void
    {
        $this->enviar()->assertCreated();

        $this->enviar(['dias_solicitados' => 9, 'fecha_fin' => '2026-09-09'])->assertStatus(409);

        $this->assertSame(1, Licencia::count());
    }

    public function test_rechaza_un_texto_con_extension_pdf_y_lo_anota(): void
    {
        $falso = UploadedFile::fake()->createWithContent('falso.pdf', 'https://github.com/algo/algo.git, esto no es un pdf');

        $this->enviar(['archivo' => $falso])->assertStatus(422)->assertJsonValidationErrors(['archivo']);

        $this->assertSame(0, Licencia::count());
        $this->assertSame(1, Auditoria::where('evento', 'archivo_rechazado')->count());
    }

    public function test_rechaza_un_pdf_con_javascript(): void
    {
        $peligroso = UploadedFile::fake()->createWithContent('js.pdf', $this->pdfDePrueba('<</S/JavaScript/JS(app.alert(1))>>'));

        $this->enviar(['archivo' => $peligroso])->assertStatus(422)->assertJsonValidationErrors(['archivo']);

        $this->assertSame(0, Documento::count());
    }

    public function test_rechaza_una_imagen(): void
    {
        $this->enviar(['archivo' => UploadedFile::fake()->image('foto.jpg')])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['archivo']);
    }

    public function test_rechaza_archivos_de_mas_de_10_mb(): void
    {
        $grande = UploadedFile::fake()->createWithContent('grande.pdf', $this->pdfDePrueba(str_repeat('x', 10 * 1024 * 1024)));

        $this->enviar(['archivo' => $grande])->assertStatus(422)->assertJsonValidationErrors(['archivo']);
    }

    public function test_no_registra_tipos_que_no_son_licencias_aprobadas(): void
    {
        $this->enviar(['tipo_licencia' => 'Franquicia horaria'])->assertStatus(422)->assertJsonValidationErrors(['tipo_licencia']);
    }

    public function test_la_fecha_fin_no_puede_ser_anterior_al_inicio(): void
    {
        $this->enviar(['fecha_fin' => '2026-08-01'])->assertStatus(422)->assertJsonValidationErrors(['fecha_fin']);
    }

    public function test_un_legajo_inexistente_responde_404(): void
    {
        $this->withHeader('Authorization', 'Bearer ' . $this->token)
            ->postJson('/api/legajos/999999/licencias', ['solicitud_id' => 1])
            ->assertNotFound();
    }

    public function test_sin_token_responde_401(): void
    {
        $this->enviar(conToken: false)->assertUnauthorized();
    }

    public function test_un_cliente_inactivo_responde_403(): void
    {
        $this->cliente->update(['is_active' => false]);

        $this->enviar()->assertForbidden();
    }
}

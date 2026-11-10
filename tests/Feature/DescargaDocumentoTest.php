<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Documento;
use App\Services\ArchivoSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: los documentos solo los abre quien tiene permiso, con un enlace firmado y vencible
class DescargaDocumentoTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private string $pdf;
    private Documento $documento;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        config([
            'app.key' => 'base64:' . base64_encode(random_bytes(32)),
            'sistema.documentos.optimizar' => false,
            'sistema.documentos.cifrar' => true,
        ]);

        $this->pdf = $this->pdfDePrueba('documento de prueba para abrir');
        $ruta = ArchivoSeguro::guardarContenido($this->pdf, 'dni.pdf', 'documentos');

        $this->documento = Documento::create(['ruta' => $ruta, 'nombre_original' => 'dni.pdf']);
    }

    public function test_el_administrador_abre_el_documento_descifrado(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $respuesta = $this->get(Documento::abrirDocumento($this->documento));

        $respuesta->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertSame($this->pdf, $respuesta->getContent());
        $this->assertSame(1, Auditoria::where('evento', 'ver_documento')->count());
    }

    public function test_el_archivo_guardado_en_disco_no_es_el_pdf_original(): void
    {
        $this->assertStringNotContainsString('%PDF-', Storage::disk('local')->get($this->documento->ruta));
    }

    public function test_sin_la_firma_del_enlace_no_se_abre(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $this->get(route('documentos.ver', $this->documento))->assertForbidden();
    }

    public function test_un_enlace_con_la_firma_vencida_no_se_abre(): void
    {
        $this->actingAs($this->usuario('administrador'));
        $enlace = Documento::abrirDocumento($this->documento);

        $this->travel(2)->hours();

        $this->get($enlace)->assertForbidden();
    }

    public function test_sin_iniciar_sesion_no_se_abre(): void
    {
        $this->get(Documento::abrirDocumento($this->documento))->assertRedirect();
    }

    public function test_un_empleado_no_abre_el_documento_de_otro_legajo(): void
    {
        $legajoAjeno = $this->crearLegajo();
        $this->documento->legajos()->attach($legajoAjeno->id);

        $propio = $this->crearLegajo();
        $this->actingAs($this->usuario('empleado', $propio->persona_id));

        $this->get(Documento::abrirDocumento($this->documento))->assertNotFound();
    }
}

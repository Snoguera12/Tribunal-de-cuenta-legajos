<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Services\ArchivoSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: los documentos se guardan cifrados y se pueden volver a leer
class ArchivoSeguroTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private string $pdf;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');

        config([
            'app.key' => 'base64:' . base64_encode(random_bytes(32)),
            'sistema.documentos.optimizar' => false, // sin depender de GD ni de ghostscript
            'sistema.documentos.cifrar' => true,
        ]);

        $this->pdf = $this->pdfDePrueba(str_repeat('contenido de prueba ', 50));
    }

    public function test_el_documento_se_guarda_cifrado_y_se_lee_igual(): void
    {
        $ruta = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');

        Storage::disk('local')->assertExists($ruta);
        $this->assertStringEndsWith('.pdf', $ruta);

        $guardado = Storage::disk('local')->get($ruta);
        $this->assertNotSame($this->pdf, $guardado);
        $this->assertStringStartsWith('TCS2', $guardado);
        $this->assertStringNotContainsString('%PDF-', $guardado);

        $this->assertSame($this->pdf, ArchivoSeguro::leer($ruta));
    }

    public function test_con_el_interruptor_apagado_se_guarda_sin_cifrar(): void
    {
        config(['sistema.documentos.cifrar' => false]);

        $ruta = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');

        $this->assertSame($this->pdf, Storage::disk('local')->get($ruta));
        $this->assertSame($this->pdf, ArchivoSeguro::leer($ruta));
    }

    public function test_cambiar_el_interruptor_no_deja_ilegibles_los_documentos_viejos(): void
    {
        $cifrado = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');

        config(['sistema.documentos.cifrar' => false]);
        $otro = ArchivoSeguro::guardarContenido($this->pdfDePrueba('otro documento distinto'), 'b.pdf', 'documentos');

        $this->assertSame($this->pdf, ArchivoSeguro::leer($cifrado));
        $this->assertNotNull(ArchivoSeguro::leer($otro));
    }

    public function test_el_mismo_archivo_subido_dos_veces_ocupa_lugar_una_vez(): void
    {
        $uno = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');
        $dos = ArchivoSeguro::guardarContenido($this->pdf, 'copia.pdf', 'documentos');

        $this->assertSame($uno, $dos);
        $this->assertCount(1, Storage::disk('local')->allFiles('documentos'));
    }

    public function test_archivos_distintos_tienen_nombres_distintos(): void
    {
        $uno = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');
        $dos = ArchivoSeguro::guardarContenido($this->pdfDePrueba('distinto'), 'b.pdf', 'documentos');

        $this->assertNotSame($uno, $dos);
    }

    public function test_un_documento_viejo_sin_cifrar_se_sigue_abriendo(): void
    {
        Storage::disk('local')->put('documentos/viejo.pdf', $this->pdf);

        $this->assertSame($this->pdf, ArchivoSeguro::leer('documentos/viejo.pdf'));
    }

    public function test_un_archivo_que_no_es_pdf_ni_jpg_ni_cifrado_no_se_devuelve(): void
    {
        Storage::disk('local')->put('documentos/raro.pdf', 'texto cualquiera que no es un documento');

        $this->assertNull(ArchivoSeguro::leer('documentos/raro.pdf'));
    }

    public function test_un_archivo_alterado_no_se_descifra(): void
    {
        $ruta = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');

        $alterado = Storage::disk('local')->get($ruta);
        $alterado[strlen($alterado) - 1] = $alterado[strlen($alterado) - 1] ^ "\x01";
        Storage::disk('local')->put($ruta, $alterado);

        $this->assertNull(ArchivoSeguro::leer($ruta));
    }

    public function test_con_otra_clave_no_se_puede_abrir(): void
    {
        $ruta = ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        $this->assertNull(ArchivoSeguro::leer($ruta));
    }

    public function test_cada_subida_queda_en_la_auditoria(): void
    {
        ArchivoSeguro::guardarContenido($this->pdf, 'a.pdf', 'documentos');

        $this->assertSame(1, Auditoria::where('evento', 'archivo_subido')->count());
    }
}

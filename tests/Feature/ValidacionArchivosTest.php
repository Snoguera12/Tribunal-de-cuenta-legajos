<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Incidente;
use App\Rules\ArchivoPdfOJpeg;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: solo PDF o JPG de verdad, no archivos falsos con la extensión cambiada
class ValidacionArchivosTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private function falla(UploadedFile $archivo): bool
    {
        return Validator::make(['archivo' => $archivo], ['archivo' => [new ArchivoPdfOJpeg()]])->fails();
    }

    private function archivo(string $nombre, string $contenido): UploadedFile
    {
        return UploadedFile::fake()->createWithContent($nombre, $contenido);
    }

    public function test_acepta_un_pdf_real(): void
    {
        $this->assertFalse($this->falla($this->archivo('doc.pdf', $this->pdfDePrueba())));
    }

    public function test_acepta_un_pdf_con_fuente_subconjunto_de_chrome(): void
    {
        // Chrome nombra las fuentes /AAAAAA+Arial: no se debe confundir con la acción automática /AA
        $pdf = $this->pdfDePrueba('<</Type/Font/BaseFont/AAAAAA+Arial>>');

        $this->assertFalse($this->falla($this->archivo('chrome.pdf', $pdf)));
    }

    public function test_rechaza_un_texto_con_extension_pdf(): void
    {
        $this->assertTrue($this->falla($this->archivo('falso.pdf', 'https://github.com/algo/algo.git y mas texto')));
    }

    public function test_rechaza_un_ejecutable_con_extension_jpg(): void
    {
        $this->assertTrue($this->falla($this->archivo('foto.jpg', "MZ\x90\x00" . str_repeat('A', 200))));
    }

    public function test_rechaza_un_pdf_con_javascript(): void
    {
        $pdf = $this->pdfDePrueba('<</S/JavaScript/JS(app.alert(1))>>');

        $this->assertTrue($this->falla($this->archivo('js.pdf', $pdf)));
    }

    public function test_rechaza_un_pdf_con_accion_automatica(): void
    {
        $pdf = $this->pdfDePrueba('<</Type/Catalog/OpenAction 2 0 R>>');

        $this->assertTrue($this->falla($this->archivo('accion.pdf', $pdf)));
    }

    public function test_acepta_un_pdf_con_destino_de_apertura(): void
    {
        // /OpenAction [pagina /Fit] solo indica que pagina mostrar al abrir: es inofensivo
        $pdf = $this->pdfDePrueba('<</Type/Catalog/OpenAction [3 0 R /Fit]>>');

        $this->assertFalse($this->falla($this->archivo('destino.pdf', $pdf)));
    }

    public function test_rechaza_una_apertura_con_accion_de_javascript(): void
    {
        $pdf = $this->pdfDePrueba('<</Type/Catalog/OpenAction<</S/JavaScript/JS(app.alert(1))>>>>');

        $this->assertTrue($this->falla($this->archivo('accion.pdf', $pdf)));
    }

    public function test_rechaza_un_pdf_sin_cierre(): void
    {
        $this->assertTrue($this->falla($this->archivo('cortado.pdf', "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n")));
    }

    public function test_rechaza_un_pdf_con_datos_escondidos_al_final(): void
    {
        $pdf = $this->pdfDePrueba() . str_repeat('X', 2000);

        $this->assertTrue($this->falla($this->archivo('escondido.pdf', $pdf)));
    }

    public function test_acepta_un_jpeg_real(): void
    {
        if (! function_exists('imagejpeg')) {
            $this->markTestSkipped('Falta la extensión GD de PHP.');
        }

        $this->assertFalse($this->falla(UploadedFile::fake()->image('foto.jpg', 120, 80)));
    }

    public function test_un_archivo_que_ya_no_esta_no_da_error(): void
    {
        // al quitar el archivo del formulario el valor deja de ser una subida: no hay nada que revisar
        $resultado = Validator::make(['archivo' => 'documentos/ya-guardado.pdf'], ['archivo' => [new ArchivoPdfOJpeg()]]);

        $this->assertFalse($resultado->fails());
    }

    public function test_un_rechazo_queda_en_la_auditoria_y_abre_un_incidente(): void
    {
        $this->falla($this->archivo('falso.pdf', 'texto que no es un pdf, nada de nada'));

        $this->assertSame(1, Auditoria::where('evento', 'archivo_rechazado')->count());
        $this->assertSame(1, Incidente::where('tipo', 'archivo_malicioso')->count());
    }

    public function test_la_comprobacion_silenciosa_no_deja_rastro(): void
    {
        $regla = new ArchivoPdfOJpeg(registrar: false);
        $archivo = $this->archivo('falso.pdf', 'texto que no es un pdf, nada de nada');

        $this->assertTrue(Validator::make(['archivo' => $archivo], ['archivo' => [$regla]])->fails());
        $this->assertSame(0, Auditoria::where('evento', 'archivo_rechazado')->count());
    }
}

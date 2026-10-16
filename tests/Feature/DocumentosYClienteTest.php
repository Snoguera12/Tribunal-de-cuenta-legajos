<?php

namespace Tests\Feature;

use App\Rules\ArchivoPdfOJpeg;
use App\Services\ArchivoSeguro;
use App\Services\DatosDelCliente;
use App\Services\OptimizadorDocumentos;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;


class DocumentosYClienteTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    // jpeg grande como el de una foto del celular
    private function jpegGrande(): string
    {
        $im = imagecreatetruecolor(3600, 2700);

        mt_srand(7);
        for ($i = 0; $i < 300; $i++) {
            imagefilledellipse($im, mt_rand(0, 3600), mt_rand(0, 2700), mt_rand(80, 800), mt_rand(80, 800),
                imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        imagefilter($im, IMG_FILTER_GAUSSIAN_BLUR);

        ob_start();
        imagejpeg($im, null, 97);

        return ob_get_clean();
    }


    public function test_cifrar_y_descifrar_devuelve_lo_mismo(): void
    {
        $contenido = '%PDF-1.4 ' . str_repeat('hola ', 500) . ' %%EOF';

        $guardado = ArchivoSeguro::encriptar($contenido);

        $this->assertStringStartsWith('TCS2', $guardado);
        $this->assertStringNotContainsString('hola', $guardado);
        $this->assertSame($contenido, ArchivoSeguro::desencriptar($guardado));
    }


    public function test_dos_cifrados_del_mismo_contenido_son_distintos(): void
    {
        $this->assertNotSame(ArchivoSeguro::encriptar('abc'), ArchivoSeguro::encriptar('abc'));
    }


    public function test_un_archivo_alterado_no_se_abre(): void
    {
        $guardado = ArchivoSeguro::encriptar('%PDF-1.4 contenido importante %%EOF');

        $alterado = $guardado;
        $alterado[strlen($alterado) - 3] = $alterado[strlen($alterado) - 3] ^ "\x01";

        $this->assertNull(ArchivoSeguro::desencriptar($alterado));
        $this->assertNull(ArchivoSeguro::desencriptar('basura'));
    }


    public function test_con_otra_clave_no_se_abre(): void
    {
        $guardado = ArchivoSeguro::encriptar('%PDF-1.4 x %%EOF');

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        $this->assertNull(ArchivoSeguro::desencriptar($guardado));
    }


    public function test_los_archivos_del_formato_viejo_siguen_abriendo(): void
    {
        $contenido = '%PDF-1.4 viejo %%EOF';

        // formato anterior: iv + tag + cifrado, sin la marca TCS2
        $viejo = substr(ArchivoSeguro::encriptar($contenido), 4);

        $this->assertSame($contenido, ArchivoSeguro::desencriptar($viejo));
    }


    public function test_el_nombre_depende_del_contenido(): void
    {
        $this->assertSame(ArchivoSeguro::nombreDesdeContenido('abc'), ArchivoSeguro::nombreDesdeContenido('abc'));
        $this->assertNotSame(ArchivoSeguro::nombreDesdeContenido('abc'), ArchivoSeguro::nombreDesdeContenido('abd'));
        $this->assertSame(48, strlen(ArchivoSeguro::nombreDesdeContenido('abc')));
    }


    public function test_un_jpeg_grande_se_reduce_mucho_y_sigue_siendo_jpeg(): void
    {
        $original = $this->jpegGrande();

        $r = OptimizadorDocumentos::optimizar($original);

        $this->assertEquals('jpeg-gd', $r['metodo']);
        $this->assertLessThan(strlen($original) * 0.5, strlen($r['contenido']), 'tiene que ahorrar mas de la mitad');
        $this->assertTrue(ArchivoSeguro::esPdfOJpeg($r['contenido']));

        [$ancho, $alto] = getimagesizefromstring($r['contenido']);
        $this->assertLessThanOrEqual(config('sistema.documentos.jpeg_lado_maximo'), max($ancho, $alto));
    }


    public function test_lo_optimizado_igual_pasa_la_validacion_de_seguridad(): void
    {
        $r = OptimizadorDocumentos::optimizar($this->jpegGrande());

        $archivo = UploadedFile::fake()->createWithContent('foto.jpg', $r['contenido']);

        $this->assertFalse(Validator::make(['f' => $archivo], ['f' => [new ArchivoPdfOJpeg()]])->fails());
    }


    public function test_un_jpeg_chico_que_no_ahorra_se_deja_igual(): void
    {
        // ya esta comprimido mas fuerte que lo que haria el optimizador
        $im = imagecreatetruecolor(200, 200);
        mt_srand(3);
        for ($i = 0; $i < 3000; $i++) {
            imagesetpixel($im, mt_rand(0, 199), mt_rand(0, 199), imagecolorallocate($im, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }
        ob_start();
        imagejpeg($im, null, 30);
        $chico = ob_get_clean();

        $r = OptimizadorDocumentos::optimizar($chico);

        $this->assertTrue($r['contenido'] === $chico, 'tendria que quedar el original');
        $this->assertEquals('original', $r['metodo']);
    }


    public function test_apagar_la_optimizacion_guarda_el_original(): void
    {
        config(['sistema.documentos.optimizar' => false]);

        $original = $this->jpegGrande();

        $this->assertSame($original, OptimizadorDocumentos::optimizar($original)['contenido']);
    }


    public function test_un_pdf_escaneado_se_reduce_con_ghostscript(): void
    {
        if (! OptimizadorDocumentos::hayGhostscript()) {
            $this->markTestSkipped('No hay ghostscript instalado');
        }

        // pdf de una hoja A4 con una foto de 3600x2700 adentro (unos 300 dpi)
        $jpeg = $this->jpegGrande();
        $ancho = 595; $alto = 842;

        $pdf = "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj\n"
            . "2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj\n"
            . "3 0 obj<</Type/Page/Parent 2 0 R/MediaBox[0 0 {$ancho} {$alto}]/Contents 4 0 R/Resources<</XObject<</Im0 5 0 R>>>>>>endobj\n"
            . "4 0 obj<</Length 36>>stream\nq {$ancho} 0 0 {$alto} 0 0 cm /Im0 Do Q\nendstream endobj\n"
            . "5 0 obj<</Type/XObject/Subtype/Image/Width 3600/Height 2700/ColorSpace/DeviceRGB/BitsPerComponent 8/Filter/DCTDecode/Length " . strlen($jpeg) . ">>stream\n"
            . $jpeg . "\nendstream endobj\ntrailer<</Root 1 0 R/Size 6>>\n%%EOF";

        $r = OptimizadorDocumentos::optimizar($pdf);

        $this->assertEquals('pdf-gs', $r['metodo']);
        $this->assertLessThan(strlen($pdf) * 0.6, strlen($r['contenido']));
        $this->assertStringStartsWith('%PDF-', $r['contenido']);
    }


    public function test_sacar_la_mac_de_distintos_formatos(): void
    {
        $this->assertSame('aa:bb:cc:dd:ee:ff', DatosDelCliente::sacarMac('192.168.1.5 dev eth0 lladdr aa:bb:cc:dd:ee:ff REACHABLE'));
        $this->assertSame('aa:bb:cc:dd:ee:ff', DatosDelCliente::sacarMac('  192.168.1.5   AA-BB-CC-DD-EE-FF   dinamico'));
        $this->assertNull(DatosDelCliente::sacarMac('192.168.1.5 dev eth0  FAILED'));
        $this->assertNull(DatosDelCliente::sacarMac('00:00:00:00:00:00'));
        $this->assertNull(DatosDelCliente::sacarMac('ff:ff:ff:ff:ff:ff'));
    }


    public function test_la_mac_no_se_busca_para_ips_publicas_ni_invalidas(): void
    {
        $this->assertNull(DatosDelCliente::mac('8.8.8.8'));
        $this->assertNull(DatosDelCliente::mac('127.0.0.1'));
        $this->assertNull(DatosDelCliente::mac('no-es-una-ip; rm -rf /'));
        $this->assertNull(DatosDelCliente::mac(null));
    }


    public function test_navegador_sistema_y_dispositivo(): void
    {
        $celu = DatosDelCliente::navegador('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 Version/17.0 Mobile/15E148 Safari/604.1');
        $this->assertEquals(['navegador' => 'Safari 17', 'so' => 'iOS', 'dispositivo' => 'celular'], $celu);

        $linux = DatosDelCliente::navegador('Mozilla/5.0 (X11; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0');
        $this->assertEquals(['navegador' => 'Firefox 121', 'so' => 'Linux', 'dispositivo' => 'pc'], $linux);

        $edge = DatosDelCliente::navegador('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0.0.0 Safari/537.36 Edg/120.0.0.0');
        $this->assertEquals('Edge 120', $edge['navegador']);
    }


    // jpeg de unos pocos KB que dice medir 30000x30000 px (bomba de descompresion)
    private function jpegBomba(): string
    {
        $im = imagecreatetruecolor(16, 16);
        ob_start();
        imagejpeg($im, null, 80);
        $jpeg = ob_get_clean();

        // se cambia el alto y ancho en el encabezado SOF0 (FF C0)
        $pos = strpos($jpeg, "\xFF\xC0");
        $jpeg = substr_replace($jpeg, pack('nn', 30000, 30000), $pos + 5, 4);

        return $jpeg;
    }


    public function test_una_imagen_con_dimensiones_enormes_se_rechaza(): void
    {
        $archivo = UploadedFile::fake()->createWithContent('bomba.jpg', $this->jpegBomba());

        $this->assertTrue(Validator::make(['f' => $archivo], ['f' => [new ArchivoPdfOJpeg()]])->fails());
        $this->assertEquals(1, \App\Models\Auditoria::where('evento', 'archivo_rechazado')->count());
    }


    public function test_el_optimizador_no_abre_imagenes_que_no_entran_en_memoria(): void
    {
        // aunque la validacion se saltee, el optimizador la deja como esta sin romper nada
        $this->assertNull(OptimizadorDocumentos::jpeg($this->jpegBomba()));
    }


    public function test_el_documento_se_sirve_con_cabeceras_restrictivas(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::disk('local')->put('documentos/a.pdf', ArchivoSeguro::encriptar('%PDF-1.4 x %%EOF'));

        $usuario = \App\Models\User::create(['name' => 'F', 'email' => 'f@test.com', 'password' => 'x12345678', 'rol' => 'funcionario']);
        $link = \Illuminate\Support\Facades\URL::temporarySignedRoute('documentos.ver', now()->addMinutes(5), ['path' => 'documentos/a.pdf']);

        $r = $this->actingAs($usuario)->get($link);

        $r->assertOk();
        $this->assertStringContainsString('sandbox', $r->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
        $this->assertEquals('nosniff', $r->headers->get('X-Content-Type-Options'));
    }
}

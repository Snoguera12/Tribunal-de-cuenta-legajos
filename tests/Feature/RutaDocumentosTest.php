<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Documento;
use App\Models\User;
use App\Services\ArchivoSeguro;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;


class RutaDocumentosTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private string $ruta = 'documentos/prueba.pdf';
    private string $contenido = '%PDF-1.4 hola mundo %%EOF';


    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Storage::disk('local')->put($this->ruta, ArchivoSeguro::encriptar($this->contenido));
        Documento::create(['ruta' => $this->ruta, 'descripcion' => 'x']);
    }


    private function link(): string
    {
        return URL::temporarySignedRoute('documentos.ver', now()->addMinutes(5), ['path' => $this->ruta]);
    }


    private function usuario(string $rol): User
    {
        return User::create(['name' => $rol, 'email' => "{$rol}@test.com", 'password' => 'x12345678', 'rol' => $rol]);
    }


    public function test_el_funcionario_abre_el_documento_descifrado_y_queda_anotado(): void
    {
        $respuesta = $this->actingAs($this->usuario('funcionario'))->get($this->link());

        $respuesta->assertOk();
        $this->assertSame($this->contenido, $respuesta->getContent());
        $this->assertEquals('application/pdf', $respuesta->headers->get('Content-Type'));
        $this->assertEquals(1, Auditoria::where('evento', 'ver_documento')->count());
    }


    public function test_el_empleado_no_lo_abre_y_queda_anotado(): void
    {
        $this->actingAs($this->usuario('empleado'))->get($this->link())->assertForbidden();

        $this->assertEquals(1, Auditoria::where('evento', 'acceso_denegado')->count());
        $this->assertEquals(0, Auditoria::where('evento', 'ver_documento')->count());
    }


    public function test_sin_sesion_o_sin_firma_no_se_abre(): void
    {
        $this->get($this->link())->assertUnauthorized();

        $rrhh = $this->usuario('rrhh');

        $this->actingAs($rrhh)->get('/' . $this->ruta)->assertForbidden();
        $this->actingAs($rrhh)->get($this->link() . 'x')->assertForbidden();
    }


    public function test_no_sirve_para_leer_otros_archivos_del_disco(): void
    {
        Storage::disk('local')->put('secreto.txt', 'no tocar');

        $link = URL::temporarySignedRoute('documentos.ver', now()->addMinutes(5), ['path' => 'secreto.txt']);

        $this->actingAs($this->usuario('administrador'))->get($link)->assertNotFound();
    }


    public function test_un_archivo_que_no_se_puede_descifrar_da_incidente(): void
    {
        Storage::disk('local')->put($this->ruta, 'contenido que no es de este sistema');

        $this->actingAs($this->usuario('administrador'))->get($this->link())->assertNotFound();

        $this->assertEquals(1, Auditoria::where('evento', 'archivo_ilegible')->count());
    }
}

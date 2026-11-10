<?php

namespace Tests\Feature;

use App\Http\Middleware\SoloRedInterna;
use App\Models\Auditoria;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;


class RedInternaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        config(['sistema.red_interna.activa' => true, 'sistema.red_interna.permitidas' => ['10.0.0.0/8', '192.168.1.50']]);
    }


    private function pasar(string $ip)
    {
        $pedido = Request::create('/legajos', 'GET', server: ['REMOTE_ADDR' => $ip]);

        return app(SoloRedInterna::class)->handle($pedido, fn () => response('ok'));
    }


    public function test_las_ip_de_la_red_interna_pasan(): void
    {
        foreach (['10.1.2.3', '192.168.1.50', '127.0.0.1', '::1'] as $ip) {
            $this->assertEquals('ok', $this->pasar($ip)->getContent(), $ip);
        }
    }


    public function test_las_ip_de_afuera_reciben_403(): void
    {
        foreach (['8.8.8.8', '192.168.1.51', '11.0.0.1'] as $ip) {
            try {
                $this->pasar($ip);
                $this->fail("{$ip} no debería pasar");
            } catch (HttpException $e) {
                $this->assertEquals(403, $e->getStatusCode());
            }
        }
    }


    public function test_apagado_deja_pasar_a_todos(): void
    {
        config(['sistema.red_interna.activa' => false]);

        $this->assertEquals('ok', $this->pasar('8.8.8.8')->getContent());
    }


    public function test_un_rechazo_queda_en_la_auditoria_una_vez_por_ip(): void
    {
        foreach ([1, 2, 3] as $vez) {
            try {
                $this->pasar('8.8.8.8');
            } catch (HttpException $e) {
            }
        }

        $this->assertEquals(1, Auditoria::where('evento', 'acceso_denegado')->where('descripcion', 'like', '%8.8.8.8%')->count());
    }


    public function test_la_aplicacion_completa_rechaza_desde_afuera(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->get('/legajos/login')->assertForbidden();
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.9'])->get('/legajos/login')->assertSuccessful();
    }


    public function test_la_api_tambien_queda_adentro(): void
    {
        $this->withServerVariables(['REMOTE_ADDR' => '8.8.8.8'])->getJson('/api/personas')->assertForbidden();
    }
}

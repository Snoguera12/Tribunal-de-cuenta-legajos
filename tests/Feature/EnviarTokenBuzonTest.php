<?php

namespace Tests\Feature;

use App\Models\Token;
use App\Services\BuzonLicencias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\Request;
use Tests\TestCase;

class EnviarTokenBuzonTest extends TestCase
{
    use RefreshDatabase;

    private Token $cliente;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.licencias.buzon_url', 'https://licencias.test');
        config()->set('services.licencias.buzon_clave', null);

        $this->cliente = Token::create(['nombre' => 'Sistema de licencias', 'is_active' => true, 'is_created' => true]);
    }

    public function test_envia_el_token_a_la_ruta_del_buzon_y_marca_el_envio(): void
    {
        Http::fake(['licencias.test/api/buzon/tokens' => Http::response(['ok' => true], 201)]);

        $vence = now()->addHours(3)->startOfSecond();
        $resultado = BuzonLicencias::enviar($this->cliente, 'texto-del-token', $vence);

        $this->assertTrue($resultado['ok']);
        Http::assertSent(fn (Request $r) => $r->url() === 'https://licencias.test/api/buzon/tokens'
            && $r['nombre'] === 'Sistema de licencias'
            && $r['token'] === 'texto-del-token'
            && $r['vence_en'] === $vence->toIso8601String());
        $this->assertNotNull($this->cliente->fresh()->enviado_at);
    }

    public function test_envia_la_clave_del_buzon_si_esta_configurada(): void
    {
        config()->set('services.licencias.buzon_clave', 'clave-secreta');
        Http::fake(['*' => Http::response([], 201)]);

        BuzonLicencias::enviar($this->cliente, 'x', null);

        Http::assertSent(fn (Request $r) => $r->hasHeader('X-Buzon-Clave', 'clave-secreta'));
    }

    public function test_si_el_buzon_responde_error_no_marca_el_envio(): void
    {
        Http::fake(['*' => Http::response(['error' => 'x'], 500)]);

        $resultado = BuzonLicencias::enviar($this->cliente, 'x', null);

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('500', $resultado['mensaje']);
        $this->assertNull($this->cliente->fresh()->enviado_at);
    }

    public function test_sin_conexion_devuelve_error_sin_romper(): void
    {
        Http::fake(fn () => throw new \Illuminate\Http\Client\ConnectionException('sin red'));

        $resultado = BuzonLicencias::enviar($this->cliente, 'x', null);

        $this->assertFalse($resultado['ok']);
        $this->assertNull($this->cliente->fresh()->enviado_at);
    }

    public function test_sin_url_configurada_no_envia_nada(): void
    {
        config()->set('services.licencias.buzon_url', null);
        Http::fake();

        $resultado = BuzonLicencias::enviar($this->cliente, 'x', null);

        $this->assertFalse($resultado['ok']);
        Http::assertNothingSent();
    }
}

<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Token;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use LogicException;
use Tests\TestCase;


class TokenRevocacionTest extends TestCase
{
    use RefreshDatabase;

    private function cliente(): Token
    {
        return Token::create(['nombre' => 'Sistema de licencias', 'is_active' => true, 'is_created' => true, 'enviados' => ['buzon' => now()->toIso8601String()]]);
    }


    public function test_un_cliente_de_la_api_no_se_puede_borrar(): void
    {
        $cliente = $this->cliente();

        $this->expectException(LogicException::class);

        try {
            $cliente->delete();
        } finally {
            $this->assertNotNull(Token::find($cliente->id));
        }
    }


    public function test_ni_el_borrado_forzado_funciona(): void
    {
        $cliente = $this->cliente();

        try {
            $cliente->forceDelete();
            $this->fail('no tenía que dejar borrar');
        } catch (LogicException $e) {
            $this->assertNotNull(Token::find($cliente->id));
        }
    }


    public function test_revocar_corta_el_acceso_y_conserva_el_cliente(): void
    {
        $cliente = $this->cliente();
        $texto = $cliente->createToken('Token-prueba', ['*'], now()->addDay())->plainTextToken;

        $this->assertEquals(1, DB::table('personal_access_tokens')->count());

        $cliente->revocar();

        $cliente->refresh();
        $this->assertEquals(0, DB::table('personal_access_tokens')->count());
        $this->assertEmpty($cliente->is_active);
        $this->assertEmpty($cliente->is_created);
        $this->assertNull($cliente->enviados);

        // el token viejo ya no sirve
        $this->withToken($texto)->getJson('/api/personas')->assertUnauthorized();
    }


    public function test_revocar_queda_en_la_auditoria(): void
    {
        $cliente = $this->cliente();
        $cliente->createToken('Token-prueba', ['*'], now()->addDay());

        $cliente->revocar();

        $registro = Auditoria::where('evento', 'token_revocado')->first();

        $this->assertNotNull($registro);
        $this->assertStringContainsString('Sistema de licencias', $registro->descripcion);
        $this->assertEquals(1, $registro->extra['credenciales_borradas']);
    }


    public function test_despues_de_revocar_se_puede_generar_un_token_nuevo(): void
    {
        $cliente = $this->cliente();
        $cliente->createToken('Token-viejo', ['*'], now()->addDay());
        $cliente->revocar();

        $nuevo = $cliente->fresh()->createToken('Token-nuevo', ['*'], now()->addDay())->plainTextToken;
        $cliente->update(['is_active' => true, 'is_created' => true]);

        // sin personas cargadas la API responde 404: lo que importa es que ya no sea 401 ni 403
        $this->assertContains($this->withToken($nuevo)->getJson('/api/personas')->status(), [200, 404]);
    }


    public function test_generar_un_token_queda_anotado_sin_el_texto_del_token(): void
    {
        $cliente = $this->cliente();
        $texto = $cliente->createToken('Token-prueba', ['*'], now()->addDay())->plainTextToken;

        $cliente->anotarGeneracion(now()->addDay(), 'buzon');

        $registro = Auditoria::where('evento', 'token_generado')->first();

        $this->assertNotNull($registro);
        $this->assertStringNotContainsString($texto, json_encode(Auditoria::all()->toArray()));
    }


    public function test_nadie_puede_borrar_un_cliente_desde_el_panel(): void
    {
        $admin = User::factory()->create(['rol' => 'administrador']);
        $cliente = $this->cliente();

        $this->assertFalse($admin->can('delete', $cliente));
        $this->assertFalse($admin->can('forceDelete', $cliente));
        $this->assertTrue($admin->can('update', $cliente));
    }
}

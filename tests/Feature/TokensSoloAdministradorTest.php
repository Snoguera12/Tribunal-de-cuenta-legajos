<?php

namespace Tests\Feature;

use App\Filament\Resources\ApiClients\ApiClientResource;
use App\Filament\Resources\ApiClients\Pages\CreateApiClient;
use App\Filament\Resources\ApiClients\Pages\EditApiClient;
use App\Filament\Resources\ApiClients\Pages\ListApiClients;
use App\Models\ApiClient;
use App\Models\Auditoria;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;


class TokensSoloAdministradorTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function entrar(string $rol): User
    {
        Filament::setCurrentPanel('legajos');

        $usuario = User::create(['name' => $rol, 'email' => "{$rol}@test.com", 'password' => 'Clave12345', 'rol' => $rol, 'debe_cambiar_clave' => false]);
        $this->actingAs($usuario);

        return $usuario;
    }


    // lo que realmente se dibuja en el menu lateral para el usuario actual
    private function etiquetasDelMenu(): array
    {
        $etiquetas = [];

        foreach (Filament::getNavigation() as $grupo) {
            foreach ($grupo->getItems() as $item) {
                $etiquetas[] = $item->getLabel();
            }
        }

        return $etiquetas;
    }


    public function test_el_administrador_ve_y_usa_la_pantalla_de_tokens(): void
    {
        $this->entrar('administrador');
        $cliente = ApiClient::create(['nombre' => 'Sueldos', 'is_active' => false, 'is_token' => false]);

        $this->assertTrue(ApiClientResource::canViewAny());
        $this->assertTrue(ApiClientResource::canCreate());
        $this->assertTrue(ApiClientResource::canAccess());
        $this->assertContains('Tokens', $this->etiquetasDelMenu(), 'el administrador ve Tokens en el menu');

        Livewire::test(ListApiClients::class)->assertSuccessful()->assertCanSeeTableRecords([$cliente]);
        Livewire::test(CreateApiClient::class)->assertSuccessful();
        Livewire::test(EditApiClient::class, ['record' => $cliente->id])->assertSuccessful();
    }


    public function test_ningun_otro_rol_ve_la_pantalla_ni_el_menu_de_tokens(): void
    {
        $cliente = ApiClient::create(['nombre' => 'Sueldos', 'is_active' => true, 'is_token' => true]);

        foreach (['rrhh', 'funcionario', 'empleado'] as $rol) {

            $this->entrar($rol);

            $this->assertFalse(ApiClientResource::canViewAny(), "{$rol}: lista");
            $this->assertFalse(ApiClientResource::canCreate(), "{$rol}: crear");
            $this->assertFalse(ApiClientResource::canAccess(), "{$rol}: acceso");
            $this->assertNotContains('Tokens', $this->etiquetasDelMenu(), "{$rol}: no ve Tokens en el menu");

            foreach ([[ListApiClients::class, []], [CreateApiClient::class, []], [EditApiClient::class, ['record' => $cliente->id]]] as [$pagina, $datos]) {
                try {
                    Livewire::test($pagina, $datos)->assertForbidden();
                } catch (HttpException $e) {
                    $this->assertEquals(403, $e->getStatusCode(), "{$rol} en {$pagina}");
                }
            }
        }

        // y el cliente no se toco
        $this->assertTrue((bool) $cliente->fresh()->is_active);
        $this->assertEquals(0, Auditoria::whereIn('evento', ['token_generado', 'token_eliminado'])->count());
    }


    public function test_un_usuario_comun_no_puede_usar_la_api_aunque_tenga_sesion(): void
    {
        $cliente = ApiClient::create(['nombre' => 'Sueldos', 'is_active' => true, 'is_token' => true]);

        foreach (['administrador', 'rrhh', 'funcionario', 'empleado'] as $rol) {
            $usuario = $this->entrar($rol);

            $pedido = \Illuminate\Http\Request::create('/api/personas');
            $pedido->setUserResolver(fn () => $usuario);

            // ni siquiera el administrador entra por la API: solo los clientes con token
            $this->assertEquals(403, app(\App\Http\Controllers\Api\PersonaController::class)->index($pedido)->status(), $rol);
        }

        $this->assertNotNull($cliente);
    }
}

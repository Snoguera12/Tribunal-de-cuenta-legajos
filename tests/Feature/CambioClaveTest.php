<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Pages\CambiarClave;
use App\Http\Middleware\ForzarCambioClave;
use App\Models\Auditoria;
use App\Models\User;
use App\Notifications\UsuarioCreado;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;


class CambioClaveTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('legajos');
        config(['sistema.sesion_unica.modo' => 'apagada']);
    }


    private function usuario(array $extra = []): User
    {
        return User::create(array_merge(['name' => 'Luis', 'email' => 'luis@test.com', 'password' => 'Provisoria123', 'rol' => 'empleado'], $extra));
    }


    // ---------------------------------------------------------- usuario nuevo

    public function test_un_usuario_nuevo_queda_con_la_clave_provisoria(): void
    {
        $this->assertTrue($this->usuario()->fresh()->debe_cambiar_clave);
    }


    public function test_al_crear_un_usuario_le_llega_un_mail_sin_la_clave(): void
    {
        Notification::fake();

        $usuario = $this->usuario();

        Notification::assertSentTo($usuario, UsuarioCreado::class);

        $texto = (new UsuarioCreado())->toMail($usuario)->render();
        $texto = (string) $texto;

        $this->assertStringContainsString('Luis', $texto);
        $this->assertStringContainsString('cambies', mb_strtolower(str_replace('cambiar', 'cambies', $texto)));
        $this->assertStringNotContainsString('Provisoria123', $texto);
    }


    public function test_si_el_mail_falla_el_usuario_igual_se_crea(): void
    {
        config(['mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);

        $usuario = $this->usuario();

        $this->assertNotNull(User::find($usuario->id));
    }


    public function test_los_usuarios_que_ya_existian_no_se_obligan_a_cambiar_la_clave(): void
    {
        // como los deja la migracion: la columna vale false
        $usuario = $this->usuario(['debe_cambiar_clave' => false]);

        $this->assertFalse($usuario->fresh()->debe_cambiar_clave);
    }


    // ---------------------------------------------------------- primer ingreso

    public function test_el_primer_ingreso_queda_anotado_una_sola_vez(): void
    {
        $usuario = $this->usuario();

        foreach ([1, 2] as $vuelta) {
            // cada vuelta es un navegador nuevo, sin sesion
            auth()->forgetGuards();
            session()->flush();

            Livewire::test(Login::class)
                ->fillForm(['email' => 'luis@test.com', 'password' => 'Provisoria123'])
                ->call('authenticate');
        }

        $this->assertNotNull($usuario->fresh()->primer_ingreso_en);
        $this->assertEquals(1, Auditoria::where('evento', 'primer_ingreso')->count());
        $this->assertStringContainsString('debe cambiar', Auditoria::where('evento', 'primer_ingreso')->first()->descripcion);
    }


    // ---------------------------------------------------------- obligar a cambiarla

    private function pedido(string $ruta = '/legajos/personas', ?string $nombreDeRuta = null): Request
    {
        $pedido = Request::create($ruta);

        if ($nombreDeRuta) {
            $pedido->setRouteResolver(fn () => (new Route('GET', $ruta, []))->name($nombreDeRuta));
        }

        return $pedido;
    }


    public function test_con_la_clave_provisoria_todo_manda_a_la_pagina_de_cambio(): void
    {
        $this->actingAs($this->usuario());

        $respuesta = app(ForzarCambioClave::class)->handle($this->pedido(), fn () => response('ok'));

        $this->assertTrue($respuesta->isRedirection());
        $this->assertStringContainsString('cambiar-clave', $respuesta->headers->get('Location'));
    }


    public function test_la_pagina_de_cambio_y_el_logout_si_se_pueden_abrir(): void
    {
        $this->actingAs($this->usuario());
        $middleware = app(ForzarCambioClave::class);

        $cambio = $middleware->handle($this->pedido('/legajos/cambiar-clave', CambiarClave::getRouteName()), fn () => response('ok'));
        $salir = $middleware->handle($this->pedido('/legajos/logout', 'filament.legajos.auth.logout'), fn () => response('ok'));

        $this->assertEquals('ok', $cambio->getContent());
        $this->assertEquals('ok', $salir->getContent());
    }


    public function test_quien_ya_cambio_la_clave_no_se_redirige(): void
    {
        $this->actingAs($this->usuario(['debe_cambiar_clave' => false]));

        $respuesta = app(ForzarCambioClave::class)->handle($this->pedido(), fn () => response('ok'));

        $this->assertEquals('ok', $respuesta->getContent());
    }


    // ---------------------------------------------------------- la pagina de cambio

    public function test_el_usuario_cambia_su_clave_y_deja_de_ser_provisoria(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        Livewire::test(CambiarClave::class)
            ->fillForm(['actual' => 'Provisoria123', 'nueva' => 'MiClaveNueva456', 'confirmar' => 'MiClaveNueva456'])
            ->call('guardar')
            ->assertHasNoFormErrors()
            ->assertRedirect();

        $usuario->refresh();

        $this->assertTrue(Hash::check('MiClaveNueva456', $usuario->password));
        $this->assertFalse($usuario->debe_cambiar_clave);
        $this->assertNotNull($usuario->clave_cambiada_en);
        $this->assertEquals(1, Auditoria::where('evento', 'cambio_password')->count());

        // la clave nueva no queda escrita en la auditoria
        foreach (Auditoria::all() as $registro) {
            $this->assertStringNotContainsString('MiClaveNueva456', json_encode($registro->toArray()));
        }
    }


    public function test_no_acepta_la_clave_actual_incorrecta(): void
    {
        $this->actingAs($this->usuario());

        Livewire::test(CambiarClave::class)
            ->fillForm(['actual' => 'otra', 'nueva' => 'MiClaveNueva456', 'confirmar' => 'MiClaveNueva456'])
            ->call('guardar')
            ->assertHasErrors(['data.actual']);
    }


    public function test_no_acepta_claves_debiles_ni_iguales_a_la_actual(): void
    {
        $usuario = $this->usuario();
        $this->actingAs($usuario);

        foreach (['corta1A', 'todominusculas123', 'SOLOMAYUSCULAS123', 'SinNumerosAquiAbc'] as $debil) {
            Livewire::test(CambiarClave::class)
                ->fillForm(['actual' => 'Provisoria123', 'nueva' => $debil, 'confirmar' => $debil])
                ->call('guardar')
                ->assertHasErrors(['data.nueva']);
        }

        Livewire::test(CambiarClave::class)
            ->fillForm(['actual' => 'Provisoria123', 'nueva' => 'Provisoria123', 'confirmar' => 'Provisoria123'])
            ->call('guardar')
            ->assertHasErrors(['data.nueva']);

        Livewire::test(CambiarClave::class)
            ->fillForm(['actual' => 'Provisoria123', 'nueva' => 'MiClaveNueva456', 'confirmar' => 'OtraDistinta789'])
            ->call('guardar')
            ->assertHasErrors(['data.confirmar']);

        $this->assertTrue($usuario->fresh()->debe_cambiar_clave, 'si nada se aceptó sigue provisoria');
    }


    public function test_si_el_administrador_le_cambia_la_clave_vuelve_a_ser_provisoria(): void
    {
        $usuario = $this->usuario(['debe_cambiar_clave' => false]);
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'Clave12345', 'rol' => 'administrador']);

        $this->actingAs($admin);
        $usuario->update(['password' => 'NuevaProvisoria789']);

        $usuario->refresh();
        $this->assertTrue($usuario->debe_cambiar_clave);
        $this->assertNull($usuario->clave_cambiada_en);
    }


    public function test_la_pagina_de_cambio_no_aparece_en_el_menu(): void
    {
        $this->actingAs($this->usuario());

        $this->assertFalse(CambiarClave::shouldRegisterNavigation());
    }
}

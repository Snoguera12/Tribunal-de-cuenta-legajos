<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\ControlarSesionUnica;
use App\Models\Auditoria;
use App\Models\User;
use App\Services\SesionUnica;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;


class SesionUnicaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('legajos');
        config(['sistema.sesion_unica.modo' => 'bloquear', 'sistema.sesion_unica.inactividad_minutos' => 10]);
    }


    private function usuario(): User
    {
        return User::factory()->create(['email' => 'luis@test.com', 'rol' => 'empleado']);
    }


    private function fila(User $usuario): object
    {
        return \DB::table('users')->where('id', $usuario->id)->first();
    }


    private function entrar(string $clave = 'password')
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => 'luis@test.com', 'password' => $clave])
            ->call('authenticate');
    }


    // ---------------------------------------------------------- el servicio

    public function test_los_modos_se_reconocen(): void
    {
        foreach (['bloquear' => true, 'reemplazar' => true, 'apagada' => false] as $modo => $activa) {
            config(['sistema.sesion_unica.modo' => $modo]);
            $this->assertSame($activa, SesionUnica::activa(), $modo);
        }
    }


    public function test_no_se_guarda_el_id_real_de_la_sesion(): void
    {
        $usuario = $this->usuario();

        SesionUnica::registrar($usuario, 'id-real-de-la-sesion', '10.0.0.5');

        $fila = $this->fila($usuario);
        $this->assertNotEquals('id-real-de-la-sesion', $fila->sesion_activa_id);
        $this->assertEquals(SesionUnica::huella('id-real-de-la-sesion'), $fila->sesion_activa_id);
        $this->assertEquals('10.0.0.5', $fila->sesion_activa_ip);
    }


    public function test_otra_sesion_reciente_cuenta_como_activa_pero_la_propia_no(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'sesion-a');

        $this->assertNotNull(SesionUnica::hayOtraActiva($usuario, SesionUnica::huella('sesion-b')));
        $this->assertNull(SesionUnica::hayOtraActiva($usuario, SesionUnica::huella('sesion-a')));
    }


    public function test_una_sesion_abandonada_no_bloquea(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'sesion-a');

        $this->travel(11)->minutes();

        $this->assertNull(SesionUnica::hayOtraActiva($usuario, SesionUnica::huella('sesion-b')));
    }


    public function test_liberar_solo_si_es_la_sesion_registrada(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'sesion-a');

        SesionUnica::liberar($usuario, 'sesion-b');
        $this->assertNotNull($this->fila($usuario)->sesion_activa_id);

        SesionUnica::liberar($usuario, 'sesion-a');
        $this->assertNull($this->fila($usuario)->sesion_activa_id);
    }


    public function test_tocar_escribe_como_mucho_cada_treinta_segundos(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'sesion-a');
        $primera = $this->fila($usuario)->ultima_actividad_en;

        $this->travel(10)->seconds();
        SesionUnica::tocar($usuario->fresh());
        $this->assertEquals($primera, $this->fila($usuario)->ultima_actividad_en);

        $this->travel(25)->seconds();
        SesionUnica::tocar($usuario->fresh());
        $this->assertNotEquals($primera, $this->fila($usuario)->ultima_actividad_en);
    }


    public function test_tocar_la_actividad_no_llena_la_auditoria(): void
    {
        $usuario = $this->usuario();
        $antes = Auditoria::count();

        SesionUnica::registrar($usuario, 'sesion-a');
        $this->travel(40)->seconds();
        SesionUnica::tocar($usuario->fresh());

        $this->assertEquals($antes, Auditoria::count());
    }


    // ---------------------------------------------------------- el ingreso

    public function test_con_el_modo_bloquear_el_segundo_ingreso_se_rechaza(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'otro-navegador', '10.0.0.9');

        $this->entrar()->assertHasErrors(['data.email']);

        $this->assertGuest();
        $this->assertEquals(1, Auditoria::where('evento', 'sesion_duplicada_bloqueada')->count());
        // la sesión de antes sigue siendo la activa
        $this->assertEquals(SesionUnica::huella('otro-navegador'), $this->fila($usuario)->sesion_activa_id);
    }


    public function test_con_el_modo_bloquear_se_entra_si_la_otra_sesion_esta_abandonada(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'otro-navegador');

        $this->travel(11)->minutes();

        $this->entrar()->assertHasNoErrors();
        $this->assertAuthenticated();
    }


    public function test_con_el_modo_reemplazar_el_segundo_ingreso_pasa_y_ocupa_el_lugar(): void
    {
        config(['sistema.sesion_unica.modo' => 'reemplazar']);
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'otro-navegador');

        $this->entrar()->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertNotEquals(SesionUnica::huella('otro-navegador'), $this->fila($usuario)->sesion_activa_id);
    }


    public function test_con_el_modo_apagada_no_se_controla_nada(): void
    {
        config(['sistema.sesion_unica.modo' => 'apagada']);
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'otro-navegador');

        $this->entrar()->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertEquals(0, Auditoria::where('evento', 'sesion_duplicada_bloqueada')->count());
    }


    public function test_un_ingreso_correcto_deja_registrada_la_sesion(): void
    {
        $usuario = $this->usuario();

        $this->entrar()->assertHasNoErrors();

        $this->assertNotNull($this->fila($usuario)->sesion_activa_id);
        $this->assertNotNull($this->fila($usuario)->sesion_activa_desde);
    }


    // ---------------------------------------------------------- la sesión desplazada

    private function pedido(): Request
    {
        $pedido = Request::create('/legajos');
        $pedido->setLaravelSession(app('session.store'));

        return $pedido;
    }


    public function test_la_sesion_registrada_sigue_andando(): void
    {
        $usuario = $this->usuario();

        $pedido = $this->pedido();
        SesionUnica::registrar($usuario, $pedido->session()->getId());

        // como en un pedido real: el usuario se lee de la base con la sesión ya registrada
        $this->actingAs($usuario->fresh());

        $respuesta = app(ControlarSesionUnica::class)->handle($pedido, fn () => response('ok'));

        $this->assertEquals('ok', $respuesta->getContent());
        $this->assertAuthenticated();
    }


    public function test_una_sesion_desplazada_se_cierra_y_queda_anotado(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'la-sesion-nueva-de-otro-lado');
        $this->actingAs($usuario->fresh());

        $respuesta = app(ControlarSesionUnica::class)->handle($this->pedido(), fn () => response('ok'));

        $this->assertTrue($respuesta->isRedirection());
        $this->assertGuest();
        $this->assertEquals(1, Auditoria::where('evento', 'sesion_desplazada')->count());
    }


    public function test_una_sesion_desplazada_en_livewire_recibe_419(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'la-sesion-nueva-de-otro-lado');
        $this->actingAs($usuario->fresh());

        $pedido = $this->pedido();
        $pedido->headers->set('X-Livewire', 'true');

        try {
            app(ControlarSesionUnica::class)->handle($pedido, fn () => response('ok'));
            $this->fail('tenía que cortar la sesión');
        } catch (HttpException $e) {
            $this->assertEquals(419, $e->getStatusCode());
        }
    }


    public function test_con_el_modo_apagada_la_sesion_no_se_corta(): void
    {
        config(['sistema.sesion_unica.modo' => 'apagada']);
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'otra');
        $this->actingAs($usuario->fresh());

        $respuesta = app(ControlarSesionUnica::class)->handle($this->pedido(), fn () => response('ok'));

        $this->assertEquals('ok', $respuesta->getContent());
    }


    public function test_deshabilitar_a_un_usuario_libera_su_lugar(): void
    {
        $usuario = $this->usuario();
        SesionUnica::registrar($usuario, 'sesion-a');

        $usuario->fresh()->forceFill(['habilitado' => false])->save();

        $this->assertNull($this->fila($usuario)->sesion_activa_id);
    }
}

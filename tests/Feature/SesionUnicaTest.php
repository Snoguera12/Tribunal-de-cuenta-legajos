<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Http\Middleware\ControlarSesionUnica;
use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\User;
use App\Services\SesionUnica;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;


class SesionUnicaTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private User $usuario;


    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 10:00:00');
        Filament::setCurrentPanel('legajos');
        config(['sistema.sesion_unica.modo' => 'bloquear', 'sistema.sesion_unica.inactividad_minutos' => 10]);

        $this->usuario = User::create(['name' => 'Ana', 'email' => 'ana@test.com', 'password' => 'Clave12345', 'rol' => 'empleado']);
    }


    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }


    // cambia a "otro navegador": otra sesion y nadie logueado
    private function otroNavegador(): string
    {
        auth()->forgetGuards();
        session()->flush();
        $id = Str::random(40);
        session()->setId($id);

        return $id;
    }


    private function entrar(): ?string
    {
        return Livewire::test(Login::class)
            ->fillForm(['email' => 'ana@test.com', 'password' => 'Clave12345'])
            ->call('authenticate')
            ->errors()->first('data.email') ?: null;
    }


    public function test_el_primer_ingreso_queda_como_sesion_activa(): void
    {
        $this->assertNull($this->entrar());

        $this->assertEquals(SesionUnica::huella(session()->getId()), $this->usuario->fresh()->sesion_activa_id);
        $this->assertNotNull($this->usuario->fresh()->ultima_actividad_en);
    }


    public function test_un_segundo_ingreso_mientras_hay_otra_sesion_activa_se_rechaza(): void
    {
        $this->assertNull($this->entrar());
        $primera = $this->usuario->fresh()->sesion_activa_id;

        $this->otroNavegador();
        Carbon::setTestNow(now()->addMinutes(3));

        $mensaje = $this->entrar();

        $this->assertStringContainsString('ya tiene una sesión abierta', $mensaje);
        $this->assertGuest();
        $this->assertEquals($primera, $this->usuario->fresh()->sesion_activa_id, 'la primera sesion no se toca');
        $this->assertEquals(1, Auditoria::where('evento', 'sesion_duplicada_bloqueada')->count());
    }


    public function test_si_la_otra_sesion_estuvo_inactiva_el_nuevo_ingreso_la_reemplaza(): void
    {
        $this->assertNull($this->entrar());
        $primera = $this->usuario->fresh()->sesion_activa_id;

        $this->otroNavegador();
        Carbon::setTestNow(now()->addMinutes(11));

        $this->assertNull($this->entrar());
        $this->assertAuthenticatedAs($this->usuario);
        $this->assertNotEquals($primera, $this->usuario->fresh()->sesion_activa_id);
    }


    public function test_en_modo_reemplazar_el_segundo_ingreso_siempre_entra(): void
    {
        config(['sistema.sesion_unica.modo' => 'reemplazar']);

        $this->assertNull($this->entrar());
        $this->otroNavegador();

        $this->assertNull($this->entrar());
        $this->assertAuthenticatedAs($this->usuario);
    }


    public function test_apagada_no_controla_nada(): void
    {
        config(['sistema.sesion_unica.modo' => 'apagada']);

        $this->assertNull($this->entrar());
        $this->otroNavegador();

        $this->assertNull($this->entrar());
        $this->assertNull($this->usuario->fresh()->sesion_activa_id);
    }


    public function test_la_sesion_desplazada_se_cierra_en_su_proximo_pedido(): void
    {
        $this->assertNull($this->entrar());
        $vieja = session()->getId();

        // otra sesion tomo el lugar
        SesionUnica::registrar($this->usuario, 'sesion-nueva-de-otro-lugar', '10.0.0.9');

        $this->otroNavegador();
        session()->setId($vieja);
        $this->actingAs($this->usuario->fresh());

        $pedido = Request::create('/legajos/personas');
        $pedido->setLaravelSession(session()->driver());

        $respuesta = app(ControlarSesionUnica::class)->handle($pedido, fn () => response('ok'));

        $this->assertTrue($respuesta->isRedirection());
        $this->assertStringContainsString('login', $respuesta->headers->get('Location'));
        $this->assertGuest();
        $this->assertEquals(1, Auditoria::where('evento', 'sesion_desplazada')->count());
    }


    public function test_la_sesion_que_corresponde_sigue_y_actualiza_su_actividad(): void
    {
        $this->assertNull($this->entrar());

        Carbon::setTestNow(now()->addMinutes(2));
        $this->actingAs($this->usuario->fresh());

        $pedido = Request::create('/legajos/personas');
        $pedido->setLaravelSession(session()->driver());

        $respuesta = app(ControlarSesionUnica::class)->handle($pedido, fn () => response('ok'));

        $this->assertEquals('ok', $respuesta->getContent());
        $this->assertTrue($this->usuario->fresh()->ultima_actividad_en->gte(now()->subSeconds(5)));
    }


    public function test_una_sesion_que_ya_estaba_abierta_antes_de_la_regla_pasa_a_ser_la_activa(): void
    {
        $this->actingAs($this->usuario);

        $pedido = Request::create('/legajos');
        $pedido->setLaravelSession(session()->driver());

        app(ControlarSesionUnica::class)->handle($pedido, fn () => response('ok'));

        $this->assertEquals(SesionUnica::huella(session()->getId()), $this->usuario->fresh()->sesion_activa_id);
    }


    public function test_al_cerrar_sesion_se_libera_el_lugar(): void
    {
        $this->assertNull($this->entrar());
        $this->assertNotNull($this->usuario->fresh()->sesion_activa_id);

        event(new Logout('web', $this->usuario));

        $this->assertNull($this->usuario->fresh()->sesion_activa_id);

        // y desde otro navegador entra enseguida
        $this->otroNavegador();
        $this->assertNull($this->entrar());
    }


    public function test_cerrar_la_sesion_de_otra_persona_no_libera_el_lugar_de_este_usuario(): void
    {
        $this->assertNull($this->entrar());
        $registrada = $this->usuario->fresh()->sesion_activa_id;

        // un cierre de sesion de OTRA sesion del mismo usuario no tiene que borrar la activa
        session()->setId(Str::random(40));
        event(new Logout('web', $this->usuario));

        $this->assertEquals($registrada, $this->usuario->fresh()->sesion_activa_id);
    }


    public function test_tres_ingresos_duplicados_seguidos_abren_un_incidente(): void
    {
        $this->assertNull($this->entrar());

        foreach (range(1, 3) as $n) {
            $this->otroNavegador();
            Carbon::setTestNow(now()->addMinutes(1));
            $this->entrar();
        }

        $this->assertEquals(3, Auditoria::where('evento', 'sesion_duplicada_bloqueada')->count());
        $this->assertEquals(1, Incidente::where('tipo', 'sesion_compartida')->count());
    }


    public function test_el_administrador_puede_cerrar_la_sesion_de_un_usuario(): void
    {
        $this->assertNull($this->entrar());

        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'Clave12345', 'rol' => 'administrador']);
        $this->otroNavegador();
        $this->actingAs($admin);

        Livewire::test(\App\Filament\Resources\Users\Pages\ListUsers::class)->callTableAction('cerrar_sesion', $this->usuario);

        $this->assertNull($this->usuario->fresh()->sesion_activa_id);
        $this->assertEquals(1, Auditoria::where('evento', 'sesion_cerrada_por_admin')->count());
    }


    public function test_deshabilitar_a_un_usuario_libera_su_sesion(): void
    {
        $this->assertNull($this->entrar());

        $this->usuario->update(['habilitado' => false]);

        $this->assertNull($this->usuario->fresh()->sesion_activa_id);
    }
}

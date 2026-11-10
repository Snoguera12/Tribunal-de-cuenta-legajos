<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Auditoria;
use App\Models\LoginIntento;
use App\Models\User;
use App\Services\ControlIntentosLogin;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;


class LoginIntentosTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Filament::setCurrentPanel('legajos');
        config(['sistema.sesion_unica.modo' => 'apagada']);
    }


    private function usuario(string $rol = 'empleado'): User
    {
        return User::factory()->create(['email' => 'luis@test.com', 'rol' => $rol]);
    }


    private function fallar(string $email, int $veces): string
    {
        $mensaje = '';

        for ($i = 0; $i < $veces; $i++) {
            $mensaje = ControlIntentosLogin::registrarFallo($email);
        }

        return $mensaje;
    }


    public function test_el_primer_fallo_dice_cuantos_intentos_quedan_y_obliga_a_esperar(): void
    {
        $this->usuario();

        $mensaje = $this->fallar('luis@test.com', 1);

        $this->assertStringContainsString('Te quedan 4', $mensaje);
        $this->assertStringContainsString('Esperá', ControlIntentosLogin::bloqueoActual('luis@test.com'));

        $this->travel(21)->seconds();

        $this->assertNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));
    }


    public function test_el_cuarto_fallo_avisa_que_queda_uno_y_lo_anota(): void
    {
        $this->usuario();

        $mensaje = $this->fallar('luis@test.com', 4);

        $this->assertStringContainsString('Te quedan 1', $mensaje);
        $this->assertStringContainsString('se bloquea', $mensaje);
        $this->assertEquals(1, Auditoria::where('evento', 'login_advertencia')->count());
    }


    public function test_cinco_fallos_bloquean_una_hora(): void
    {
        $this->usuario();

        $mensaje = $this->fallar('luis@test.com', 5);

        $this->assertStringContainsString('bloqueado', $mensaje);
        $this->assertStringContainsString('temporalmente', ControlIntentosLogin::bloqueoActual('luis@test.com'));
        $this->assertEquals(1, Auditoria::where('evento', 'cuenta_bloqueada_temporal')->count());
        $this->assertEquals('temporal', ControlIntentosLogin::estadoDe(User::first()));

        $this->travel(59)->minutes();
        $this->assertNotNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));

        $this->travel(2)->minutes();
        $this->assertNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));
    }


    public function test_agotar_la_segunda_tanda_bloquea_para_siempre_y_deshabilita(): void
    {
        $usuario = $this->usuario();

        $this->fallar('luis@test.com', 5);
        $this->travel(61)->minutes();
        $this->fallar('luis@test.com', 5);

        $usuario->refresh();

        $this->assertStringContainsString('administrador', ControlIntentosLogin::bloqueoActual('luis@test.com'));
        $this->assertFalse($usuario->habilitado);
        $this->assertEquals('bloqueo_login', $usuario->deshabilitado_motivo);
        $this->assertEquals('permanente', ControlIntentosLogin::estadoDe($usuario));
        $this->assertEquals(1, Auditoria::where('evento', 'cuenta_bloqueada_permanente')->count());

        // pasa el tiempo que pase, sigue bloqueado
        $this->travel(30)->days();
        $this->assertNotNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));
    }


    public function test_el_tope_total_de_claves_incorrectas_bloquea_para_siempre(): void
    {
        config(['sistema.login.tope_total' => 3]);
        $this->usuario();

        $this->fallar('luis@test.com', 3);

        $this->assertTrue(LoginIntento::first()->permanente);
    }


    public function test_un_administrador_bloqueado_no_se_deshabilita(): void
    {
        $admin = $this->usuario('administrador');

        $this->fallar('luis@test.com', 5);
        $this->travel(61)->minutes();
        $this->fallar('luis@test.com', 5);

        $this->assertTrue($admin->fresh()->habilitado);
        $this->assertTrue(LoginIntento::first()->permanente);
    }


    public function test_si_se_frena_en_cuatro_a_los_diez_minutos_se_devuelven_los_intentos(): void
    {
        $this->usuario();

        $this->fallar('luis@test.com', 4);
        $this->travel(11)->minutes();

        $this->assertNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));
        $this->assertEquals(0, LoginIntento::first()->fallos_tanda);
        $this->assertEquals(1, LoginIntento::first()->reinicios);
        $this->assertEquals(1, Auditoria::where('evento', 'intentos_restaurados')->count());
        // el total sigue contando
        $this->assertEquals(4, LoginIntento::first()->fallos_totales);
    }


    public function test_clave_correcta_borra_los_fallos(): void
    {
        $usuario = $this->usuario();

        $this->fallar('luis@test.com', 3);
        ControlIntentosLogin::reiniciar($usuario);

        $this->assertEquals(0, LoginIntento::count());
        $this->assertNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));
    }


    public function test_el_administrador_desbloquea_y_el_usuario_vuelve_a_entrar(): void
    {
        $usuario = $this->usuario();
        $admin = User::factory()->create(['rol' => 'administrador']);

        $this->fallar('luis@test.com', 5);
        $this->travel(61)->minutes();
        $this->fallar('luis@test.com', 5);

        ControlIntentosLogin::desbloquear($usuario->fresh(), $admin);

        $this->assertTrue($usuario->fresh()->habilitado);
        $this->assertEquals(0, LoginIntento::count());
        $this->assertNull(ControlIntentosLogin::bloqueoActual('luis@test.com'));
        $this->assertEquals(1, Auditoria::where('evento', 'cuenta_desbloqueada')->count());
    }


    public function test_un_correo_que_no_existe_se_cuenta_igual_y_no_deja_filas(): void
    {
        $mensaje = $this->fallar('nadie@test.com', 1);

        $this->assertStringContainsString('Te quedan 4', $mensaje);
        $this->assertEquals(0, LoginIntento::count());
        $this->assertStringContainsString('Esperá', ControlIntentosLogin::bloqueoActual('NADIE@test.com'));
    }


    public function test_las_mayusculas_y_espacios_del_correo_no_evitan_el_conteo(): void
    {
        $this->usuario();

        ControlIntentosLogin::registrarFallo('luis@test.com');
        ControlIntentosLogin::registrarFallo('  LUIS@Test.com ');

        $this->assertEquals(2, LoginIntento::first()->fallos_tanda);
    }


    // ---------------------------------------------------------- la pantalla de ingreso

    public function test_con_clave_mala_la_pantalla_muestra_los_intentos_que_quedan(): void
    {
        $this->usuario();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'luis@test.com', 'password' => 'incorrecta'])
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
        $this->assertEquals(1, LoginIntento::first()->fallos_tanda);
        $this->assertEquals(1, Auditoria::where('evento', 'login_fallido')->count());
    }


    public function test_un_usuario_bloqueado_no_entra_ni_con_la_clave_correcta(): void
    {
        $this->usuario();
        $this->fallar('luis@test.com', 5);

        Livewire::test(Login::class)
            ->fillForm(['email' => 'luis@test.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasErrors(['data.email']);

        $this->assertGuest();
    }


    public function test_con_la_clave_correcta_entra_y_se_borran_los_fallos(): void
    {
        $this->usuario();
        $this->fallar('luis@test.com', 2);
        $this->travel(30)->seconds();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'luis@test.com', 'password' => 'password'])
            ->call('authenticate')
            ->assertHasNoErrors();

        $this->assertAuthenticated();
        $this->assertEquals(0, LoginIntento::count());
    }


    public function test_un_usuario_deshabilitado_no_entra(): void
    {
        $usuario = $this->usuario();
        $usuario->forceFill(['habilitado' => false])->save();

        Livewire::test(Login::class)
            ->fillForm(['email' => 'luis@test.com', 'password' => 'password'])
            ->call('authenticate');

        $this->assertGuest();
        $this->assertEquals(1, Auditoria::where('evento', 'acceso_denegado')->count());
    }
}

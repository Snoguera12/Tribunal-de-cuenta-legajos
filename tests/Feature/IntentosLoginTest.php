<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\LoginIntento;
use App\Models\User;
use Carbon\Carbon;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;


class IntentosLoginTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private User $usuario;


    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-10-14 10:00:00');
        Filament::setCurrentPanel('legajos');
        config(['sistema.sesion_unica.modo' => 'apagada']);

        $this->usuario = User::create(['name' => 'Ana', 'email' => 'ana@test.com', 'password' => 'Clave12345', 'rol' => 'empleado']);
    }


    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }


    // intenta ingresar y devuelve el mensaje de error del correo (o null si entro)
    private function intentar(string $clave, string $email = 'ana@test.com'): ?string
    {
        $componente = Livewire::test(Login::class)
            ->fillForm(['email' => $email, 'password' => $clave])
            ->call('authenticate');

        return $componente->errors()->first('data.email') ?: null;
    }


    private function pasan(int $segundos): void
    {
        Carbon::setTestNow(now()->addSeconds($segundos));
    }


    private function mal(): ?string
    {
        $mensaje = $this->intentar('claveMala1');
        $this->pasan(21);

        return $mensaje;
    }


    private function estado(): LoginIntento
    {
        return LoginIntento::where('user_id', $this->usuario->id)->firstOrFail();
    }


    public function test_el_primer_error_avisa_que_hay_que_esperar_20_segundos_y_quedan_4_intentos(): void
    {
        $mensaje = $this->intentar('claveMala1');

        $this->assertStringContainsString('Clave incorrecta', $mensaje);
        $this->assertStringContainsString('20 segundos', $mensaje);
        $this->assertStringContainsString('4 intento', $mensaje);
        $this->assertGuest();
    }


    public function test_dentro_de_los_20_segundos_no_se_mira_la_clave_ni_se_gasta_un_intento(): void
    {
        $this->intentar('claveMala1');

        // aunque ahora la clave sea la correcta, todavia no se puede intentar
        $this->pasan(5);
        $mensaje = $this->intentar('Clave12345');

        $this->assertStringContainsString('Esperá 15 segundos', $mensaje);
        $this->assertGuest();
        $this->assertEquals(1, $this->estado()->fallos_tanda);

        // pasados los 20 segundos entra
        $this->pasan(16);
        $this->assertNull($this->intentar('Clave12345'));
        $this->assertAuthenticatedAs($this->usuario);
    }


    public function test_cada_error_resta_un_intento_y_el_ultimo_advierte_del_bloqueo(): void
    {
        $this->assertStringContainsString('4 intento', $this->mal());
        $this->assertStringContainsString('3 intento', $this->mal());
        $this->assertStringContainsString('2 intento', $this->mal());

        $cuarto = $this->mal();
        $this->assertStringContainsString('1 intento', $cuarto);
        $this->assertStringContainsString('se bloquea por 60 minutos', $cuarto);

        // al cuarto error el administrador queda advertido
        $this->assertEquals(1, Auditoria::where('evento', 'login_advertencia')->count());
    }


    public function test_al_quinto_error_se_bloquea_una_hora(): void
    {
        foreach (range(1, 4) as $n) {
            $this->mal();
        }

        $quinto = $this->intentar('claveMala1');

        $this->assertStringContainsString('bloqueado por 60 minutos', $quinto);
        $this->assertStringContainsString('5 intentos más', $quinto);

        // durante la hora no entra ni con la clave correcta
        $this->pasan(30 * 60);
        $this->assertStringContainsString('bloqueado temporalmente', $this->intentar('Clave12345'));
        $this->assertGuest();

        // queda registrado y el administrador recibe aviso
        $this->assertEquals(1, Auditoria::where('evento', 'cuenta_bloqueada_temporal')->count());
        $this->assertEquals(1, Incidente::where('tipo', 'cuenta_bloqueada')->count());
    }


    public function test_pasada_la_hora_vuelve_a_tener_5_intentos(): void
    {
        foreach (range(1, 5) as $n) {
            $this->mal();
        }

        $this->pasan(61 * 60);

        $this->assertStringContainsString('4 intento', $this->intentar('claveMala1'));
    }


    public function test_pasada_la_hora_puede_entrar_con_la_clave_correcta_y_todo_se_reinicia(): void
    {
        foreach (range(1, 5) as $n) {
            $this->mal();
        }

        $this->pasan(61 * 60);

        $this->assertNull($this->intentar('Clave12345'));
        $this->assertAuthenticatedAs($this->usuario);
        $this->assertNull(LoginIntento::where('user_id', $this->usuario->id)->first());
    }


    public function test_si_fallan_los_5_intentos_de_la_segunda_tanda_el_bloqueo_es_definitivo(): void
    {
        foreach (range(1, 5) as $n) {
            $this->mal();
        }

        $this->pasan(61 * 60);

        foreach (range(1, 4) as $n) {
            $this->mal();
        }

        $quinto = $this->intentar('claveMala1');

        $this->assertStringContainsString('bloqueado', $quinto);
        $this->assertStringContainsString('administrador', $quinto);

        $usuario = $this->usuario->fresh();
        $this->assertFalse($usuario->habilitado);
        $this->assertEquals('bloqueo_login', $usuario->deshabilitado_motivo);

        // pasen las horas que pasen, sigue bloqueado
        $this->pasan(48 * 3600);
        $this->assertStringContainsString('administrador', $this->intentar('Clave12345'));
        $this->assertGuest();

        $this->assertEquals(1, Auditoria::where('evento', 'cuenta_bloqueada_permanente')->count());
        $this->assertEquals('critica', Incidente::where('tipo', 'cuenta_bloqueada_permanente')->first()->severidad);
    }


    public function test_el_administrador_puede_dar_permiso_otra_vez(): void
    {
        foreach (range(1, 5) as $n) {
            $this->mal();
        }
        $this->pasan(61 * 60);
        foreach (range(1, 5) as $n) {
            $this->mal();
        }
        $this->assertFalse($this->usuario->fresh()->habilitado);

        // desde la lista de usuarios
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@test.com', 'password' => 'Clave12345', 'rol' => 'administrador']);
        $this->actingAs($admin);

        Livewire::test(ListUsers::class)->callTableAction('desbloquear', $this->usuario);

        $this->assertTrue($this->usuario->fresh()->habilitado);
        $this->assertEquals(1, Auditoria::where('evento', 'cuenta_desbloqueada')->count());

        auth()->logout();
        $this->assertNull($this->intentar('Clave12345'));
        $this->assertAuthenticatedAs($this->usuario);
    }


    public function test_rrhh_no_puede_desbloquear(): void
    {
        $rrhh = User::create(['name' => 'RRHH', 'email' => 'rrhh@test.com', 'password' => 'Clave12345', 'rol' => 'rrhh']);
        LoginIntento::create(['user_id' => $this->usuario->id, 'permanente' => true]);

        $this->actingAs($rrhh);

        Livewire::test(ListUsers::class)->assertTableActionHidden('desbloquear', $this->usuario);
    }


    public function test_por_consola_se_puede_desbloquear_si_queda_bloqueado_el_unico_admin(): void
    {
        LoginIntento::create(['user_id' => $this->usuario->id, 'permanente' => true]);
        $this->usuario->forceFill(['habilitado' => false, 'deshabilitado_motivo' => 'bloqueo_login'])->save();

        $this->assertEquals(0, Artisan::call('usuarios:desbloquear', ['email' => 'ANA@test.com']));

        $this->assertTrue($this->usuario->fresh()->habilitado);
        $this->assertEquals(1, Artisan::call('usuarios:desbloquear', ['email' => 'nadie@test.com']));
    }


    public function test_antes_de_los_10_minutos_no_se_devuelven_los_intentos(): void
    {
        foreach (range(1, 4) as $n) {
            $this->mal();
        }

        // pasaron solo 5 minutos: siguen los 4 intentos gastados y el 5to error ya bloquea
        $this->pasan(5 * 60);

        $this->assertStringContainsString('bloqueado por 60 minutos', $this->intentar('claveMala1'));
        $this->assertEquals(0, Auditoria::where('evento', 'intentos_restaurados')->count());
    }


    public function test_al_frenarse_en_4_y_esperar_10_minutos_vuelve_a_probar(): void
    {
        foreach (range(1, 4) as $n) {
            $this->mal();
        }

        $this->pasan(10 * 60);

        // se le devolvieron los intentos: 5 nuevos, y queda anotado
        $this->assertStringContainsString('4 intento', $this->intentar('claveMala1'));
        $this->assertEquals(1, Auditoria::where('evento', 'intentos_restaurados')->count());
        $this->assertEquals(1, $this->estado()->reinicios);
    }


    public function test_el_tope_total_es_de_12_claves_incorrectas(): void
    {
        // 4 mal, espera, 4 mal, espera, 4 mal = 12
        foreach ([1, 2] as $vuelta) {
            foreach (range(1, 4) as $n) {
                $this->mal();
            }
            $this->pasan(10 * 60);
        }

        foreach (range(1, 3) as $n) {
            $this->mal();
        }

        $this->assertTrue($this->usuario->fresh()->habilitado);

        // la numero 12 lo bloquea de forma definitiva
        $this->assertStringContainsString('administrador', $this->intentar('claveMala1'));
        $this->assertFalse($this->usuario->fresh()->habilitado);
        $this->assertEquals(12, $this->estado()->fallos_totales);
    }


    public function test_los_reinicios_de_10_minutos_tienen_un_maximo(): void
    {
        config(['sistema.login.reinicios_maximos' => 1, 'sistema.login.tope_total' => 99]);

        foreach (range(1, 4) as $n) {
            $this->mal();
        }
        $this->pasan(10 * 60);
        foreach (range(1, 4) as $n) {
            $this->mal();
        }
        $this->pasan(10 * 60);

        // el segundo reinicio ya no existe: sigue con 4 gastados y el siguiente error lo bloquea
        $this->assertStringContainsString('bloqueado por 60 minutos', $this->intentar('claveMala1'));
        $this->assertEquals(1, $this->estado()->reinicios);
    }


    public function test_un_correo_que_no_existe_recibe_los_mismos_mensajes_y_no_deja_registros(): void
    {
        $primero = $this->intentar('claveMala1', 'fantasma@test.com');

        $this->assertStringContainsString('Clave incorrecta', $primero);
        $this->assertStringContainsString('4 intento', $primero);

        $this->pasan(5);
        $this->assertStringContainsString('Esperá 15 segundos', $this->intentar('claveMala1', 'fantasma@test.com'));

        foreach (range(1, 4) as $n) {
            $this->pasan(21);
            $mensaje = $this->intentar('claveMala1', 'fantasma@test.com');
        }

        $this->assertStringContainsString('bloqueado por 60 minutos', $mensaje);
        $this->assertEquals(0, LoginIntento::count());
        $this->assertEquals(0, Incidente::where('tipo', 'cuenta_bloqueada')->count());
    }


    public function test_un_usuario_deshabilitado_por_baja_no_gasta_intentos_ni_recibe_pistas(): void
    {
        $this->usuario->forceFill(['habilitado' => false, 'deshabilitado_motivo' => 'baja_legajo'])->save();

        $mensaje = $this->intentar('Clave12345');

        // mensaje comun de filament, sin decir que esta deshabilitado
        $this->assertStringNotContainsString('deshabilit', (string) $mensaje);
        $this->assertStringNotContainsString('intento', (string) $mensaje);
        $this->assertNull(LoginIntento::where('user_id', $this->usuario->id)->first());
    }


    public function test_la_clave_correcta_borra_los_errores_anteriores(): void
    {
        $this->mal();
        $this->mal();
        $this->assertEquals(2, $this->estado()->fallos_tanda);

        $this->assertNull($this->intentar('Clave12345'));

        $this->assertNull(LoginIntento::where('user_id', $this->usuario->id)->first());
    }


    public function test_el_correo_no_distingue_mayusculas(): void
    {
        $this->intentar('claveMala1', 'ANA@Test.com');
        $this->pasan(21);

        $this->assertEquals(1, $this->estado()->fallos_tanda);
        $this->assertStringContainsString('3 intento', $this->intentar('claveMala1', 'ana@TEST.com'));
    }
}

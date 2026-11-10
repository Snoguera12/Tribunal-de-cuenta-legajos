<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Incidente;
use App\Notifications\IncidenteAbierto;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;

// requisito: los hechos sospechosos abren un incidente y avisan a los administradores
class DetectorIncidentesTest extends TestCase
{
    use CreaLegajos, RefreshDatabase;

    private function erroresDelSistema(int $cantidad): void
    {
        for ($i = 0; $i < $cantidad; $i++) {
            Auditoria::registrar(evento: 'error_sistema', descripcion: 'Error de prueba ' . $i);
        }
    }

    public function test_tres_accesos_denegados_seguidos_abren_un_incidente(): void
    {
        $usuario = $this->usuario('empleado');

        for ($i = 0; $i < 3; $i++) {
            Auditoria::registrar(evento: 'acceso_denegado', descripcion: 'Intento de acceso', actor: $usuario);
        }

        $incidente = Incidente::where('tipo', 'acceso_no_autorizado')->first();

        $this->assertNotNull($incidente);
        $this->assertSame('alta', $incidente->severidad);
        $this->assertSame($usuario->id, $incidente->actor_id);
        $this->assertSame(1, Auditoria::where('evento', 'incidente_abierto')->count());
    }

    public function test_dos_accesos_denegados_no_abren_incidente(): void
    {
        $usuario = $this->usuario('empleado');

        for ($i = 0; $i < 2; $i++) {
            Auditoria::registrar(evento: 'acceso_denegado', descripcion: 'Intento de acceso', actor: $usuario);
        }

        $this->assertSame(0, Incidente::count());
    }

    public function test_cinco_ingresos_fallidos_contra_una_cuenta_abren_un_incidente(): void
    {
        for ($i = 0; $i < 5; $i++) {
            Auditoria::registrar(evento: 'login_fallido', descripcion: 'Clave incorrecta', extra: ['email' => 'victima@example.com']);
        }

        $this->assertSame(1, Incidente::where('tipo', 'ataque_cuenta')->count());
    }

    public function test_cinco_errores_del_sistema_abren_un_incidente_una_sola_vez(): void
    {
        $this->erroresDelSistema(7);

        $this->assertSame(1, Incidente::where('tipo', 'fallas_repetidas')->count());
    }

    public function test_cambiar_el_rol_de_un_usuario_abre_un_incidente(): void
    {
        $usuario = $this->usuario('empleado');

        $usuario->update(['rol' => 'administrador']);

        $this->assertSame(1, Auditoria::where('evento', 'cambio_rol')->count());
        $this->assertSame(1, Incidente::where('tipo', 'cambio_privilegios')->count());
    }

    public function test_un_incidente_avisa_a_los_administradores_en_el_panel(): void
    {
        $admin = $this->usuario('administrador');
        $empleado = $this->usuario('empleado');

        $this->erroresDelSistema(5);

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame(0, $empleado->notifications()->count());
    }

    public function test_el_correo_se_manda_solo_si_esta_activado(): void
    {
        Notification::fake();
        config(['sistema.alertas.correo' => false, 'sistema.alertas.destinos' => ['sistemas@example.com']]);

        $this->erroresDelSistema(5);

        Notification::assertSentOnDemandTimes(IncidenteAbierto::class, 0);
    }

    public function test_el_correo_sale_con_la_alerta_activada(): void
    {
        Notification::fake();
        config(['sistema.alertas.correo' => true, 'sistema.alertas.desde' => 'alta', 'sistema.alertas.destinos' => ['sistemas@example.com']]);

        $this->erroresDelSistema(5);

        Notification::assertSentOnDemand(IncidenteAbierto::class);
    }

    public function test_un_incidente_menos_grave_que_el_minimo_no_manda_correo(): void
    {
        Notification::fake();
        config(['sistema.alertas.correo' => true, 'sistema.alertas.desde' => 'critica', 'sistema.alertas.destinos' => ['sistemas@example.com']]);

        $this->erroresDelSistema(5); // los errores repetidos son de gravedad alta

        Notification::assertSentOnDemandTimes(IncidenteAbierto::class, 0);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\User;
use App\Notifications\IncidenteAbierto;
use App\Notifications\ResumenDiario;
use App\Services\DetectorIncidentes;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;


class AlertasCorreoTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function admin(string $email = 'admin@test.com'): User
    {
        return User::create(['name' => 'Admin', 'email' => $email, 'password' => 'Clave12345', 'rol' => 'administrador']);
    }


    public function test_por_defecto_no_se_manda_correo(): void
    {
        Notification::fake();
        $this->admin();

        DetectorIncidentes::abrir('fuerza_bruta', 'alta', 'prueba');

        // la notificacion interna del panel si sale, el correo no
        Notification::assertSentOnDemandTimes(IncidenteAbierto::class, 0);
    }


    public function test_con_las_alertas_prendidas_le_llega_a_los_administradores_y_a_los_extra(): void
    {
        Notification::fake();
        config(['sistema.alertas.correo' => true, 'sistema.alertas.destinos' => ['sistemas@tribunal.test']]);

        $this->admin();
        User::create(['name' => 'Empleado', 'email' => 'empleado@test.com', 'password' => 'Clave12345', 'rol' => 'empleado']);

        DetectorIncidentes::abrir('fuerza_bruta', 'alta', 'prueba');

        Notification::assertSentOnDemand(IncidenteAbierto::class, function ($notificacion, $canales, $destino) {
            $correos = $destino->routes['mail'];

            return in_array('admin@test.com', $correos) && in_array('sistemas@tribunal.test', $correos) && ! in_array('empleado@test.com', $correos);
        });
    }


    public function test_un_administrador_deshabilitado_no_recibe_correos(): void
    {
        $this->admin('uno@test.com');
        $otro = $this->admin('dos@test.com');
        $otro->forceFill(['habilitado' => false])->save();

        $this->assertEquals(['uno@test.com'], DetectorIncidentes::destinosCorreo());
    }


    public function test_los_incidentes_leves_no_mandan_correo(): void
    {
        Notification::fake();
        config(['sistema.alertas.correo' => true]);
        $this->admin();

        DetectorIncidentes::abrir('fuerza_bruta', 'aviso', 'poco importante');

        Notification::assertSentOnDemandTimes(IncidenteAbierto::class, 0);
    }


    public function test_si_falla_el_correo_el_incidente_igual_queda_guardado(): void
    {
        config(['sistema.alertas.correo' => true, 'mail.default' => 'smtp', 'mail.mailers.smtp.host' => '127.0.0.1', 'mail.mailers.smtp.port' => 1]);
        $this->admin();

        $incidente = DetectorIncidentes::abrir('fuerza_bruta', 'critica', 'prueba');

        $this->assertNotNull($incidente);
        $this->assertDatabaseHas('incidentes', ['id' => $incidente->id], 'historico');
    }


    public function test_el_resumen_diario_se_manda_solo_si_esta_prendido(): void
    {
        Notification::fake();
        $this->admin();
        Auditoria::registrar('login_fallido', 'x');

        Artisan::call('sistema:resumen-diario');
        Notification::assertSentOnDemandTimes(ResumenDiario::class, 0);

        config(['sistema.alertas.correo' => true]);
        Artisan::call('sistema:resumen-diario');

        Notification::assertSentOnDemand(ResumenDiario::class, fn ($n) => $n->datos['Ingresos fallidos'] === 1);
    }
}

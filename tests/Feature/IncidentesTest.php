<?php

namespace Tests\Feature;

use App\Filament\Resources\Auditorias\AuditoriaResource;
use App\Filament\Resources\Incidentes\IncidenteResource;
use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\Persona;
use App\Models\User;
use App\Rules\ArchivoPdfOJpeg;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;


class IncidentesTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function desdeIp(string $ip): void
    {
        request()->server->set('REMOTE_ADDR', $ip);
    }


    private function usuario(string $rol = 'empleado', string $email = null): User
    {
        return User::create([
            'name' => ucfirst($rol), 'email' => $email ?? "{$rol}@test.com",
            'password' => 'x12345678', 'rol' => $rol,
        ]);
    }


    public function test_cinco_fallos_desde_la_misma_ip_abren_un_incidente_una_sola_vez(): void
    {
        $this->desdeIp('10.0.0.5');

        foreach (range(1, 7) as $i) {
            event(new Failed('web', null, ['email' => "x{$i}@test.com", 'password' => 'mala']));
        }

        $incidentes = Incidente::where('tipo', 'fuerza_bruta')->get();

        $this->assertCount(1, $incidentes);
        $this->assertEquals('10.0.0.5', $incidentes->first()->ip);
        $this->assertEquals('abierto', $incidentes->first()->estado);
    }


    public function test_muchos_fallos_contra_una_cuenta_desde_distintas_ips(): void
    {
        foreach (range(1, 5) as $i) {
            $this->desdeIp("10.0.1.{$i}");
            event(new Failed('web', null, ['email' => 'victima@test.com', 'password' => 'mala']));
        }

        $this->assertEquals(1, Incidente::where('tipo', 'ataque_cuenta')->count());
    }


    public function test_entrar_despues_de_varios_fallos_es_incidente(): void
    {
        $usuario = $this->usuario();
        $this->desdeIp('10.0.0.9');

        foreach (range(1, 3) as $i) {
            event(new Failed('web', $usuario, ['email' => $usuario->email, 'password' => 'mala']));
        }

        event(new Login('web', $usuario, false));

        $this->assertEquals(1, Incidente::where('tipo', 'login_tras_fallos')->count());
    }


    public function test_ingresar_desde_una_ip_nueva_se_anota(): void
    {
        $usuario = $this->usuario();

        $this->desdeIp('10.0.0.1');
        event(new Login('web', $usuario, false));

        $this->desdeIp('10.0.0.1');
        event(new Login('web', $usuario, false));
        $this->assertEquals(0, Auditoria::where('evento', 'sesion_nueva_ip')->count());

        $this->desdeIp('10.0.0.77');
        event(new Login('web', $usuario, false));
        $this->assertEquals(1, Auditoria::where('evento', 'sesion_nueva_ip')->count());
    }


    public function test_se_guarda_la_ip_y_el_equipo(): void
    {
        $this->desdeIp('192.168.1.50');
        request()->headers->set('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 Chrome/120.0 Safari/537.36');

        Auditoria::registrar('login', 'prueba');

        $registro = Auditoria::latest('id')->first();

        $this->assertEquals('192.168.1.50', $registro->ip);
        $this->assertStringContainsString('Windows 10/11', $registro->sistema_operativo);
        $this->assertStringContainsString('Chrome 120', $registro->sistema_operativo);
        $this->assertEquals('pc', $registro->dispositivo);
        $this->assertNotNull($registro->peticion);
    }


    public function test_borrar_muchos_registros_seguidos_es_critico(): void
    {
        $admin = $this->usuario('administrador');
        $this->actingAs($admin);

        foreach (range(1, 10) as $i) {
            Persona::create(['nombre' => 'P' . $i, 'apellido' => 'X', 'dni' => 100 + $i, 'cuil' => 200 + $i, 'fecha_de_nacimiento' => '1990-01-01'])->delete();
        }

        $incidente = Incidente::where('tipo', 'borrado_masivo')->first();

        $this->assertNotNull($incidente);
        $this->assertEquals('critica', $incidente->severidad);
        $this->assertEquals($admin->id, $incidente->actor_id);
    }


    public function test_un_archivo_sospechoso_queda_registrado_con_su_hash(): void
    {
        $archivo = UploadedFile::fake()->createWithContent('falso.pdf', "%PDF-1.4\n<script>alert(1)</script>\n%%EOF");

        $valida = Validator::make(['f' => $archivo], ['f' => [new ArchivoPdfOJpeg()]])->fails();

        $this->assertTrue($valida);
        $this->assertEquals(1, Auditoria::where('evento', 'archivo_rechazado')->count());
        $this->assertEquals(1, Incidente::where('tipo', 'archivo_malicioso')->count());
        $this->assertEquals(64, strlen(Auditoria::where('evento', 'archivo_rechazado')->first()->extra['sha256']));
    }


    public function test_cambiar_el_rol_de_un_usuario_es_incidente_y_avisa_al_admin(): void
    {
        $admin = $this->usuario('administrador');
        $otro = $this->usuario('empleado');

        $this->actingAs($admin);
        $otro->update(['rol' => 'rrhh']);

        $this->assertEquals(1, Auditoria::where('evento', 'cambio_rol')->count());
        $this->assertEquals(1, Incidente::where('tipo', 'cambio_privilegios')->count());
        $this->assertEquals(1, $admin->notifications()->count());
        $this->assertEquals(0, $otro->notifications()->count());
    }


    public function test_cambiar_la_clave_se_anota_sin_guardar_la_clave(): void
    {
        $usuario = $this->usuario();
        $usuario->update(['password' => 'nuevaClave987']);

        $this->assertEquals(1, Auditoria::where('evento', 'cambio_password')->count());

        foreach (Auditoria::all() as $registro) {
            $this->assertStringNotContainsString('nuevaClave987', json_encode($registro->toArray()));
        }
    }


    public function test_solo_el_administrador_ve_auditoria_e_incidentes(): void
    {
        foreach (['empleado', 'funcionario', 'rrhh'] as $rol) {
            $this->actingAs($this->usuario($rol));

            $this->assertFalse(AuditoriaResource::canViewAny(), "{$rol} no deberia ver la auditoria");
            $this->assertFalse(IncidenteResource::canViewAny(), "{$rol} no deberia ver incidentes");
        }

        $this->actingAs($this->usuario('administrador'));

        $this->assertTrue(AuditoriaResource::canViewAny());
        $this->assertTrue(IncidenteResource::canViewAny());
    }
}

<?php

namespace Tests\Feature;

use App\Filament\Resources\Legajos\Pages\ViewLegajo;
use App\Filament\Resources\Personas\Pages\ViewPersona;
use App\Filament\Widgets\UltimosAgentesWidget;
use App\Models\ActividadReciente;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use Tests\TestCase;


// dos administradores (TIC y RRHH) y los ultimos agentes que RRHH vio o modifico
class SectorYAgentesRecientesTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private int $n = 0;


    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('legajos');
    }


    private function usuario(string $rol, array $extra = []): User
    {
        $this->n++;

        return User::create(['name' => "{$rol} {$this->n}", 'email' => "{$rol}{$this->n}@test.com", 'password' => 'x12345678', 'rol' => $rol, ...$extra]);
    }


    private function agente(): Persona
    {
        $this->n++;

        $persona = Persona::create(['nombre' => "Nombre{$this->n}", 'apellido' => 'Apellido', 'dni' => 30000000 + $this->n, 'cuil' => 20000000000 + $this->n,
            'email' => "a{$this->n}@test.com", 'genero' => 0, 'estado_civil' => 0, 'fecha_de_nacimiento' => '1990-01-01']);

        Legajo::create(['num_legajo' => 5000 + $this->n, 'estado' => true, 'fecha_de_ingreso' => '2020-01-01', 'tipo_contrato' => 2, 'persona_id' => $persona->id,
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'CAT 1'], ['descripcion' => 'x'])->id,
            'cargo_id' => Cargo::firstOrCreate(['nombre' => 'Administrativo'])->id,
            'area_id' => Area::firstOrCreate(['nombre' => 'Personal'])->id]);

        return $persona;
    }


    // ---------------------------------------------------------- dos administradores

    public function test_hay_un_administrador_de_tic_y_otro_de_rrhh(): void
    {
        $tic = $this->usuario('administrador', ['sector' => 'tic']);
        $rrhh = $this->usuario('administrador', ['sector' => 'rrhh']);
        $comun = $this->usuario('rrhh');

        $this->assertTrue($tic->isAdminTic());
        $this->assertFalse($tic->isAdminRrhh());
        $this->assertTrue($rrhh->isAdminRrhh());
        $this->assertTrue($tic->isAdmin() && $rrhh->isAdmin());
        $this->assertFalse($comun->isAdminTic() || $comun->isAdminRrhh());
        $this->assertStringContainsString('TIC', $tic->sector_nombre);
    }


    public function test_el_comando_crea_el_administrador_con_su_sector(): void
    {
        $this->assertSame(0, Artisan::call('sistema:crear-admin', ['email' => 'tic@test.com', '--sector' => 'tic']));
        $this->assertSame(0, Artisan::call('sistema:crear-admin', ['email' => 'rrhh@test.com', '--sector' => 'RRHH']));

        $this->assertSame('tic', User::where('email', 'tic@test.com')->value('sector'));
        $this->assertSame('rrhh', User::where('email', 'rrhh@test.com')->value('sector'));
        $this->assertSame('administrador', User::where('email', 'rrhh@test.com')->value('rol'));

        $this->assertSame(1, Artisan::call('sistema:crear-admin', ['email' => 'x@test.com', '--sector' => 'otro']));
        $this->assertNull(User::where('email', 'x@test.com')->first());
    }


    // ---------------------------------------------------------- ultimos agentes

    public function test_ver_un_agente_lo_anota_como_visto(): void
    {
        $rrhh = $this->usuario('rrhh');
        $agente = $this->agente();

        $this->actingAs($rrhh);
        Livewire::test(ViewPersona::class, ['record' => $agente->id]);

        $fila = ActividadReciente::where('user_id', $rrhh->id)->first();
        $this->assertSame('agente', $fila->tipo);
        $this->assertSame($agente->id, $fila->registro_id);
        $this->assertNotNull($fila->visto_en);
        $this->assertNull($fila->modificado_en);
        $this->assertSame('Vio', $fila->ultima_accion);
        $this->assertSame('Legajo ' . $agente->legajos()->first()->num_legajo, $fila->numero);
    }


    public function test_ver_el_legajo_tambien_cuenta_para_el_agente(): void
    {
        $rrhh = $this->usuario('rrhh');
        $agente = $this->agente();
        $legajo = $agente->legajos()->first();

        $this->actingAs($rrhh);
        Livewire::test(ViewLegajo::class, ['record' => $legajo->id]);

        $this->assertSame(1, ActividadReciente::where('user_id', $rrhh->id)->where('registro_id', $agente->id)->count());
    }


    public function test_modificar_un_agente_o_su_legajo_lo_anota_como_modificado(): void
    {
        $rrhh = $this->usuario('rrhh');
        $agente = $this->agente();

        $this->actingAs($rrhh);
        $agente->update(['telefono' => '3854111111']);

        $fila = ActividadReciente::where('user_id', $rrhh->id)->first();
        $this->assertNotNull($fila->modificado_en);
        $this->assertSame('Modificó', $fila->ultima_accion);

        // el legajo modificado cuenta para la persona dueña
        $otro = $this->agente();
        $otro->legajos()->first()->update(['tipo_contrato' => 1]);
        $this->assertTrue(ActividadReciente::where('user_id', $rrhh->id)->where('registro_id', $otro->id)->exists());

        // una fila por agente: ver y modificar no se repite
        $this->assertSame(2, ActividadReciente::where('user_id', $rrhh->id)->count());
    }


    public function test_solo_cuenta_lo_que_hace_rrhh_o_un_administrador(): void
    {
        $agente = $this->agente();

        foreach (['funcionario', 'empleado'] as $rol) {
            $this->actingAs($this->usuario($rol));
            $agente->update(['telefono' => '385' . random_int(1000000, 9999999)]);
        }

        $this->assertSame(0, ActividadReciente::count());

        $this->actingAs($this->usuario('administrador', ['sector' => 'tic']));
        $agente->update(['telefono' => '3855555555']);
        $this->assertSame(1, ActividadReciente::count());
    }


    public function test_modificar_un_usuario_cuenta_pero_los_cambios_de_sesion_no(): void
    {
        $rrhh = $this->usuario('rrhh');
        $empleado = $this->usuario('empleado');

        $this->actingAs($rrhh);

        // cosas automaticas (sesion, intentos) no son "modificar al usuario"
        $empleado->update(['ultima_actividad_en' => now()]);
        $this->assertSame(0, ActividadReciente::count());

        $empleado->update(['name' => 'Otro nombre']);
        $fila = ActividadReciente::first();
        $this->assertSame('usuario', $fila->tipo);
        $this->assertSame($empleado->id, $fila->registro_id);
        $this->assertSame('Usuario #' . $empleado->id, $fila->numero);

        // lo que uno se hace a si mismo no cuenta
        $rrhh->update(['name' => 'Yo mismo']);
        $this->assertSame(1, ActividadReciente::count());
    }


    public function test_el_widget_muestra_solo_lo_mio_y_los_ultimos_diez(): void
    {
        $rrhh = $this->usuario('rrhh');
        $otroRrhh = $this->usuario('rrhh');
        $agentes = collect(range(1, 12))->map(fn () => $this->agente());

        foreach ($agentes as $i => $agente) {
            $this->actingAs($rrhh);
            ActividadReciente::registrar('agente', $agente->id, 'visto');
            ActividadReciente::where('user_id', $rrhh->id)->where('registro_id', $agente->id)->update(['updated_at' => now()->subMinutes(100 - $i)]);
        }

        // actividad de otra persona de RRHH: no aparece
        $this->actingAs($otroRrhh);
        $ajeno = $this->agente();
        ActividadReciente::registrar('agente', $ajeno->id, 'modificado');

        $this->actingAs($rrhh);
        $prueba = Livewire::test(UltimosAgentesWidget::class);

        $registros = ActividadReciente::where('user_id', $rrhh->id)->orderByDesc('updated_at')->limit(10)->get();
        $this->assertCount(10, $registros);
        $prueba->assertCanSeeTableRecords($registros)->assertCountTableRecords(10);

        // los 2 mas viejos se cortan y el de otra persona no esta
        $prueba->assertCanNotSeeTableRecords(ActividadReciente::where('user_id', $otroRrhh->id)->get());
    }


    public function test_el_widget_solo_lo_ven_rrhh_y_administradores(): void
    {
        foreach (['rrhh' => true, 'administrador' => true, 'funcionario' => false, 'empleado' => false] as $rol => $debeVerlo) {
            $this->actingAs($this->usuario($rol));
            $this->assertSame($debeVerlo, UltimosAgentesWidget::canView(), "rol {$rol}");
        }
    }
}

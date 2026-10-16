<?php

namespace Tests\Feature;

use App\Filament\Pages\Auth\Login;
use App\Models\Area;
use App\Models\Auditoria;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;


class HabilitacionUsuariosTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private int $n = 0;


    // persona con usuario y un legajo de alta
    private function empleado(string $rol = 'empleado'): array
    {
        $this->n++;

        $persona = Persona::create(['nombre' => "P{$this->n}", 'apellido' => 'X', 'dni' => 100 + $this->n, 'cuil' => 200 + $this->n, 'fecha_de_nacimiento' => '1990-01-01']);

        $usuario = User::create(['name' => "u{$this->n}", 'email' => "u{$this->n}@test.com", 'password' => 'Clave12345', 'rol' => $rol, 'persona_id' => $persona->id]);

        return [$persona, $usuario, $this->legajo($persona)];
    }


    private function legajo(Persona $persona, bool $alta = true): Legajo
    {
        $this->n++;

        return Legajo::create([
            'num_legajo' => $this->n, 'estado' => $alta, 'fecha_de_ingreso' => now(), 'tipo_contrato' => 1,
            'persona_id' => $persona->id,
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'C'], ['descripcion' => 'd'])->id,
            'cargo_id' => Cargo::firstOrCreate(['nombre' => 'Cargo'])->id,
            'area_id' => Area::firstOrCreate(['nombre' => 'Area'])->id,
        ]);
    }


    public function test_al_dar_de_baja_el_legajo_se_deshabilita_el_usuario(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();

        $this->assertTrue($usuario->fresh()->habilitado);

        $legajo->update(['estado' => false]);

        $usuario->refresh();

        $this->assertFalse($usuario->habilitado);
        $this->assertEquals('baja_legajo', $usuario->deshabilitado_motivo);
        $this->assertNotNull($usuario->deshabilitado_en);
        $this->assertEquals(1, Auditoria::where('evento', 'usuario_deshabilitado')->count());
    }


    public function test_un_usuario_deshabilitado_no_puede_usar_el_panel_aunque_tenga_sesion(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();
        $panel = Filament::getPanel('legajos');

        $this->assertTrue($usuario->canAccessPanel($panel));

        $legajo->update(['estado' => false]);

        $this->assertFalse($usuario->fresh()->canAccessPanel($panel));
    }


    public function test_al_deshabilitar_se_cierran_sus_sesiones(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();

        DB::table('sessions')->insert(['id' => 'abc', 'user_id' => $usuario->id, 'payload' => 'x', 'last_activity' => time()]);
        DB::table('sessions')->insert(['id' => 'otra', 'user_id' => 9999, 'payload' => 'x', 'last_activity' => time()]);

        $legajo->update(['estado' => false]);

        $this->assertEquals(0, DB::table('sessions')->where('user_id', $usuario->id)->count());
        $this->assertEquals(1, DB::table('sessions')->where('user_id', 9999)->count());
    }


    public function test_al_dar_de_alta_otra_vez_se_vuelve_a_habilitar(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();

        $legajo->update(['estado' => false]);
        $this->assertFalse($usuario->fresh()->habilitado);

        $legajo->update(['estado' => true]);

        $usuario->refresh();
        $this->assertTrue($usuario->habilitado);
        $this->assertNull($usuario->deshabilitado_motivo);
        $this->assertNull($usuario->deshabilitado_en);
        $this->assertEquals(1, Auditoria::where('evento', 'usuario_habilitado')->count());
    }


    public function test_si_le_queda_otro_legajo_de_alta_sigue_habilitado(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();
        $otro = $this->legajo($persona);

        $legajo->update(['estado' => false]);
        $this->assertTrue($usuario->fresh()->habilitado);

        $otro->update(['estado' => false]);
        $this->assertFalse($usuario->fresh()->habilitado);
    }


    public function test_borrar_el_legajo_tambien_cuenta_como_baja(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();

        $legajo->delete();

        $this->assertFalse($usuario->fresh()->habilitado);

        $legajo->restore();

        $this->assertTrue($usuario->fresh()->habilitado);
    }


    public function test_lo_deshabilitado_a_mano_no_se_vuelve_a_habilitar_solo(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();

        $usuario->update(['habilitado' => false]);
        $this->assertEquals('manual', $usuario->fresh()->deshabilitado_motivo);

        $legajo->update(['estado' => false]);
        $legajo->update(['estado' => true]);

        $this->assertFalse($usuario->fresh()->habilitado, 'una baja y un alta no pisan lo que decidio el administrador');
    }


    public function test_al_administrador_no_se_lo_deshabilita_solo(): void
    {
        [$persona, $admin, $legajo] = $this->empleado('administrador');

        $legajo->update(['estado' => false]);

        $this->assertTrue($admin->fresh()->habilitado);
        $this->assertEquals(1, Auditoria::where('evento', 'usuario_no_deshabilitado')->count());
    }


    public function test_pasar_el_legajo_a_otra_persona_revisa_a_las_dos(): void
    {
        [$personaA, $usuarioA, $legajo] = $this->empleado();
        [$personaB, $usuarioB, $legajoB] = $this->empleado();

        $legajoB->update(['estado' => false]);
        $this->assertFalse($usuarioB->fresh()->habilitado);

        // el legajo de A pasa a B: A se queda sin legajo activo y B recupera uno
        $legajo->update(['persona_id' => $personaB->id]);

        $this->assertFalse($usuarioA->fresh()->habilitado);
        $this->assertTrue($usuarioB->fresh()->habilitado);
    }


    public function test_borrar_la_persona_deshabilita_al_usuario_y_restaurarla_lo_habilita(): void
    {
        [$persona, $usuario] = $this->empleado();

        $persona->delete();
        $this->assertEquals('persona_eliminada', $usuario->fresh()->deshabilitado_motivo);

        $persona->restore();
        $this->assertTrue($usuario->fresh()->habilitado);
    }


    public function test_el_comando_corrige_los_que_ya_estaban_mal(): void
    {
        [$persona, $usuario, $legajo] = $this->empleado();

        // un cambio hecho sin eventos (por ejemplo, con el seeder)
        DB::table('legajos')->where('id', $legajo->id)->update(['estado' => false]);
        $this->assertTrue($usuario->fresh()->habilitado);

        Artisan::call('usuarios:sincronizar-habilitacion');

        $this->assertFalse($usuario->fresh()->habilitado);
    }


    public function test_un_deshabilitado_no_puede_iniciar_sesion(): void
    {
        Filament::setCurrentPanel('legajos');

        [$persona, $usuario, $legajo] = $this->empleado();
        $legajo->update(['estado' => false]);

        Livewire::test(Login::class)
            ->fillForm(['email' => $usuario->email, 'password' => 'Clave12345'])
            ->call('authenticate');

        $this->assertGuest();

        // queda una sola vez como acceso denegado y no suma como clave mala
        $this->assertEquals(1, Auditoria::where('evento', 'acceso_denegado')->where('descripcion', 'like', '%deshabilitado%')->count());
        $this->assertEquals(0, Auditoria::where('evento', 'login_fallido')->count());
    }
}

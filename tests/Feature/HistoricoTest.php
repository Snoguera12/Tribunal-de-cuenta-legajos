<?php

namespace Tests\Feature;

use App\Filament\Pages\HistorialDatos;
use App\Models\Auditoria;
use App\Services\HistoricoService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Tests\Concerns\CreaLegajos;
use Tests\TestCase;


class HistoricoTest extends TestCase
{
    use RefreshDatabase, CreaLegajos;

    protected function setUp(): void
    {
        parent::setUp();

        Filament::setCurrentPanel('legajos');
        config(['sistema.sesion_unica.modo' => 'apagada']);
    }


    public function test_al_modificar_una_fila_se_guarda_como_estaba_antes(): void
    {
        $admin = $this->usuario('administrador');
        $this->actingAs($admin);

        $persona = $this->crearLegajo(['nombre' => 'Ana'])->persona;
        $persona->forceFill(['nombre' => 'Beatriz'])->save();

        $filas = DB::table('h_personas')->where('id', $persona->id)->get();

        $this->assertCount(1, $filas);
        $this->assertEquals('Ana', $filas[0]->nombre);
        $this->assertEquals(1, $filas[0]->hist_version);
        $this->assertEquals('UPDATE', $filas[0]->hist_operacion);
        $this->assertEquals($admin->id, $filas[0]->hist_user_id);

        $persona->forceFill(['nombre' => 'Carla'])->save();

        $segunda = DB::table('h_personas')->where('id', $persona->id)->where('hist_version', 2)->first();
        $this->assertEquals('Beatriz', $segunda->nombre);
    }


    public function test_un_cambio_que_no_es_real_no_deja_version(): void
    {
        $usuario = $this->usuario('empleado');

        $usuario->forceFill(['ultima_actividad_en' => now(), 'sesion_activa_ip' => '10.0.0.1'])->save();

        $this->assertTrue(! Schema::hasTable('h_users') || DB::table('h_users')->count() === 0);
    }


    public function test_la_clave_nunca_se_copia_al_historial(): void
    {
        $usuario = $this->usuario('empleado');
        $hash = $usuario->password;

        $usuario->forceFill(['name' => 'Otro nombre', 'password' => 'ClaveNueva12345'])->save();

        $this->assertFalse(Schema::hasColumn('h_users', 'password'));
        $this->assertStringNotContainsString($hash, json_encode(DB::table('h_users')->get()));
    }


    public function test_restaurar_vuelve_a_la_version_y_deja_el_estado_de_ahora_como_otra_version(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $persona = $this->crearLegajo(['nombre' => 'Ana'])->persona;
        $persona->forceFill(['nombre' => 'Beatriz'])->save();

        $this->assertNull(HistoricoService::restaurar('personas', $persona->id, 1));

        $this->assertEquals('Ana', $persona->fresh()->nombre);
        // lo que había antes de restaurar también quedó guardado
        $this->assertEquals('Beatriz', DB::table('h_personas')->where('id', $persona->id)->where('hist_version', 2)->value('nombre'));
        $this->assertTrue(Auditoria::where('descripcion', 'like', 'Restauró personas #' . $persona->id . '%')->exists());
    }


    public function test_restaurar_rechaza_tablas_y_versiones_que_no_existen(): void
    {
        $this->assertNotNull(HistoricoService::restaurar('auditorias', 1, 1));
        $this->assertNotNull(HistoricoService::restaurar('personas', 999, 1));
    }


    public function test_el_comando_copia_lo_que_ya_estaba_cargado_una_sola_vez(): void
    {
        $persona = $this->crearLegajo()->persona;

        $this->artisan('historico:sincronizar', ['--copiar-actual' => true])->assertSuccessful();
        $this->artisan('historico:sincronizar', ['--copiar-actual' => true])->assertSuccessful();

        $this->assertEquals(1, DB::table('h_personas')->where('id', $persona->id)->where('hist_operacion', 'BASE')->count());
    }


    public function test_solo_el_administrador_abre_la_pantalla(): void
    {
        $this->actingAs($this->usuario('empleado'));
        Livewire::test(HistorialDatos::class)->assertForbidden();

        $this->actingAs($this->usuario('administrador'));
        Livewire::test(HistorialDatos::class)->assertSuccessful();
    }


    public function test_el_administrador_restaura_desde_la_pantalla(): void
    {
        $this->actingAs($this->usuario('administrador'));

        $persona = $this->crearLegajo(['nombre' => 'Ana'])->persona;
        $persona->forceFill(['nombre' => 'Beatriz'])->save();

        Livewire::test(HistorialDatos::class)
            ->set('tabla', 'personas')
            ->set('registro', (string) $persona->id)
            ->call('restaurar', 1);

        $this->assertEquals('Ana', $persona->fresh()->nombre);
    }
}

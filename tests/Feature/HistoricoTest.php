<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Persona;
use App\Models\User;
use App\Services\HistoricoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;


class HistoricoTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function persona(array $extra = []): Persona
    {
        return Persona::create(array_merge([
            'nombre' => 'Ana', 'apellido' => 'Lopez', 'dni' => 30111222,
            'cuil' => 27301112228, 'fecha_de_nacimiento' => '1990-01-01',
        ], $extra));
    }


    private function historial(string $tabla)
    {
        return DB::connection('historico')->table('h_' . $tabla)->orderBy('hist_id');
    }


    public function test_las_tablas_historicas_existen_despues_de_migrar(): void
    {
        foreach (config('sistema.tablas_historico') as $tabla) {
            $this->assertTrue(Schema::connection('historico')->hasTable('h_' . $tabla), "falta h_{$tabla}");
        }

        // y no estan en la base principal
        $this->assertFalse(Schema::hasTable('h_personas'));
        $this->assertFalse(Schema::hasTable('auditorias'));
    }


    public function test_al_modificar_se_guarda_la_version_anterior(): void
    {
        $persona = $this->persona();

        $persona->update(['nombre' => 'Beatriz']);

        $fila = $this->historial('personas')->first();

        $this->assertEquals('UPDATE', $fila->hist_operacion);
        $this->assertEquals(1, $fila->hist_version);
        $this->assertEquals($persona->id, $fila->id);
        $this->assertEquals('Ana', $fila->nombre);          // lo que habia antes
        $this->assertEquals('Lopez', $fila->apellido);      // fila completa, no solo lo que cambio
        $this->assertEquals('Beatriz', $persona->fresh()->nombre);
    }


    public function test_cada_cambio_suma_una_version(): void
    {
        $persona = $this->persona();

        $persona->update(['nombre' => 'B']);
        $persona->update(['nombre' => 'C']);
        $persona->update(['nombre' => 'D']);

        $versiones = HistoricoService::versiones('personas', $persona->id);

        $this->assertEquals([3, 2, 1], $versiones->pluck('hist_version')->all());
        $this->assertEquals(['C', 'B', 'Ana'], $versiones->pluck('nombre')->all());
    }


    public function test_se_guarda_quien_hizo_el_cambio(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'a@test.com', 'password' => 'x12345678', 'rol' => 'administrador']);
        $persona = $this->persona();

        $this->actingAs($admin);
        $persona->update(['telefono' => '123']);

        $fila = $this->historial('personas')->first();

        $this->assertEquals($admin->id, $fila->hist_user_id);
        $this->assertEquals('Admin', $fila->hist_user_nombre);
    }


    public function test_al_borrar_queda_la_fila_y_se_puede_recuperar(): void
    {
        $persona = $this->persona();
        $persona->delete();

        $this->assertEquals('DELETE', $this->historial('personas')->first()->hist_operacion);
        $this->assertNotNull(Persona::withTrashed()->find($persona->id));

        $persona->forceDelete();

        $this->assertNull(Persona::withTrashed()->find($persona->id));
        $this->assertEquals(['DELETE', 'FORCE_DELETE'], $this->historial('personas')->pluck('hist_operacion')->all());
    }


    public function test_restaurar_borrado_suave_queda_anotado(): void
    {
        $persona = $this->persona();
        $persona->delete();
        $persona->restore();

        $this->assertEquals(1, Auditoria::where('evento', 'restaurado')->count());
        $this->assertNull($persona->fresh()->deleted_at);
    }


    public function test_el_historico_no_guarda_el_remember_token(): void
    {
        $usuario = User::create(['name' => 'U', 'email' => 'u@test.com', 'password' => 'x12345678', 'rol' => 'empleado']);
        $usuario->forceFill(['remember_token' => 'token-secreto'])->save();
        $usuario->update(['name' => 'Otro']);

        $fila = (array) $this->historial('users')->get()->last();

        $this->assertArrayNotHasKey('remember_token', $fila);
        $this->assertStringNotContainsString('token-secreto', json_encode($fila));
    }


    public function test_copiar_lo_actual_crea_la_version_base_una_sola_vez(): void
    {
        $this->persona();

        $this->assertEquals(1, HistoricoService::copiarActual('personas'));
        $this->assertEquals(0, HistoricoService::copiarActual('personas'));

        $this->assertEquals('BASE', $this->historial('personas')->first()->hist_operacion);
    }


    public function test_si_la_tabla_gana_una_columna_el_historico_la_agrega(): void
    {
        Schema::table('personas', fn ($t) => $t->string('apodo')->nullable());

        $this->assertStringContainsString('apodo', HistoricoService::sincronizarTabla('personas'));
        $this->assertTrue(Schema::connection('historico')->hasColumn('h_personas', 'apodo'));
        $this->assertEquals('al día', HistoricoService::sincronizarTabla('personas'));
    }


    public function test_restaurar_una_version_vieja(): void
    {
        $persona = $this->persona();
        $persona->update(['nombre' => 'Cambiado']);

        $codigo = Artisan::call('historico:restaurar', [
            'tabla' => 'personas', 'id' => $persona->id, 'version' => 1, '--si' => true,
        ]);

        $this->assertEquals(0, $codigo);
        $this->assertEquals('Ana', $persona->fresh()->nombre);
        $this->assertEquals(1, Auditoria::where('severidad', 'alta')->where('descripcion', 'like', '%histórico%')->count());
    }


    public function test_si_la_transaccion_se_deshace_no_queda_nada_anotado(): void
    {
        try {
            DB::transaction(function () {
                $persona = $this->persona(['dni' => 1, 'cuil' => 2]);
                $persona->update(['nombre' => 'X']);
                throw new \RuntimeException('falla');
            });
        } catch (\RuntimeException $e) {
        }

        $this->assertEquals(0, Auditoria::count());
        $this->assertEquals(0, $this->historial('personas')->count());
    }


    public function test_las_auditorias_de_la_primera_version_se_pasan_a_la_base_aparte(): void
    {
        // como quedaba antes: tabla en la base principal
        Schema::create('auditorias', function ($t) {
            $t->id();
            $t->string('actor_tipo')->default('usuario');
            $t->string('evento');
            $t->text('descripcion');
            $t->timestamp('created_at')->nullable();
        });

        DB::table('auditorias')->insert(['id' => 900, 'evento' => 'login', 'descripcion' => 'vieja', 'created_at' => now()]);

        (include database_path('migrations/2026_10_12_000004_mover_auditorias_a_la_base_historico.php'))->up();

        $this->assertFalse(Schema::hasTable('auditorias'));
        $this->assertEquals('vieja', DB::connection('historico')->table('auditorias')->where('id', 900)->value('descripcion'));
    }
}

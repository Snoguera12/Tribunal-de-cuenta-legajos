<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Incidente;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;


class CadenaAuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    protected function setUp(): void
    {
        parent::setUp();

        @unlink(Auditoria::rutaAncla());

        foreach (range(1, 6) as $n) {
            Auditoria::registrar('login', "evento {$n}", antes: ['a' => $n, 'b' => ['z' => 1, 'y' => 2]], extra: ['clave' => 'valor ñ']);
        }
    }


    protected function tearDown(): void
    {
        \Carbon\Carbon::setTestNow();
        @unlink(Auditoria::rutaAncla());

        parent::tearDown();
    }


    private function tabla()
    {
        return DB::connection('historico')->table('auditorias');
    }


    public function test_una_cadena_sin_tocar_es_valida_y_cada_hash_sigue_al_anterior(): void
    {
        $r = Auditoria::verificarCadena();

        $this->assertTrue($r['ok'], implode(' | ', $r['problemas']));
        $this->assertEquals(6, $r['revisados']);

        $filas = $this->tabla()->orderBy('id')->get();
        $this->assertEquals(str_repeat('0', 64), $filas[0]->hash_previo);
        $this->assertEquals($filas[0]->hash, $filas[1]->hash_previo);
        $this->assertEquals(64, strlen($filas[5]->hash));
    }


    public function test_se_detecta_un_registro_modificado(): void
    {
        $id = $this->tabla()->orderBy('id')->skip(2)->value('id');

        $this->tabla()->where('id', $id)->update(['descripcion' => 'cambiado a mano']);

        $r = Auditoria::verificarCadena();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString("#{$id}", $r['problemas'][0]);
        $this->assertStringContainsString('modificado', $r['problemas'][0]);
    }


    public function test_se_detecta_un_cambio_en_los_valores_antes_y_despues(): void
    {
        $id = $this->tabla()->orderBy('id')->value('id');

        $this->tabla()->where('id', $id)->update(['valores_anteriores' => json_encode(['a' => 999])]);

        $this->assertFalse(Auditoria::verificarCadena()['ok']);
    }


    public function test_se_detecta_un_registro_borrado_del_medio(): void
    {
        $id = $this->tabla()->orderBy('id')->skip(2)->value('id');

        $this->tabla()->where('id', $id)->delete();

        $r = Auditoria::verificarCadena();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('no sigue al anterior', implode(' ', $r['problemas']));
    }


    public function test_se_detecta_un_registro_falso_insertado_sin_la_clave(): void
    {
        $ultimo = $this->tabla()->orderByDesc('id')->first();

        // alguien inserta una fila a mano con un hash inventado
        $this->tabla()->insert([
            'actor_tipo' => 'usuario', 'evento' => 'login', 'descripcion' => 'falso', 'categoria' => 'acceso',
            'severidad' => 'info', 'created_at' => now(), 'hash_previo' => $ultimo->hash, 'hash' => hash('sha256', 'inventado'),
        ]);

        $this->assertFalse(Auditoria::verificarCadena()['ok']);
    }


    public function test_se_detecta_que_borraron_los_ultimos_registros(): void
    {
        // primera verificacion: queda anotado cual es el ultimo
        $this->assertTrue(Auditoria::verificarCadena()['ok']);

        $this->tabla()->orderByDesc('id')->limit(1)->delete();

        $r = Auditoria::verificarCadena();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('falta o cambió', implode(' ', $r['problemas']));
    }


    public function test_los_registros_anteriores_a_la_cadena_se_saltean(): void
    {
        $this->tabla()->insert(['id' => 9999, 'actor_tipo' => 'usuario', 'evento' => 'login', 'descripcion' => 'vieja', 'categoria' => 'acceso', 'severidad' => 'info', 'created_at' => '2025-01-01 00:00:00']);

        // una fila vieja (sin hash) no rompe la verificacion
        Auditoria::registrar('login', 'nueva');

        $r = Auditoria::verificarCadena();

        $this->assertEquals(1, $r['sin_hash']);
    }


    public function test_el_comando_avisa_y_abre_un_incidente_critico(): void
    {
        $this->assertEquals(0, Artisan::call('auditoria:verificar'));

        $this->tabla()->orderBy('id')->limit(1)->update(['descripcion' => 'tocado']);

        $this->assertEquals(1, Artisan::call('auditoria:verificar'));
        $this->assertEquals(1, Auditoria::where('evento', 'auditoria_alterada')->count());
        $this->assertEquals('critica', Incidente::where('tipo', 'auditoria_alterada')->first()->severidad);
    }


    public function test_el_hash_no_se_puede_rehacer_con_otra_clave_de_la_app(): void
    {
        $fila = $this->tabla()->orderBy('id')->first();

        config(['app.key' => 'base64:' . base64_encode(random_bytes(32))]);

        // con otra clave los hashes ya no coinciden: sin la APP_KEY no se puede falsificar la cadena
        $this->assertFalse(Auditoria::verificarCadena()['ok']);
        $this->assertNotEquals($fila->hash, Auditoria::calcularHash((array) $fila, $fila->hash_previo));
    }


    // 4 registros de hace dos anios y los 6 de hoy
    private function conViejos(): void
    {
        $this->tabla()->delete();
        \Illuminate\Support\Facades\Storage::fake('backups');

        \Carbon\Carbon::setTestNow('2024-06-01 10:00:00');
        foreach (range(1, 4) as $n) {
            Auditoria::registrar('login', "viejo {$n}");
        }

        \Carbon\Carbon::setTestNow('2026-10-14 10:00:00');
        foreach (range(1, 6) as $n) {
            Auditoria::registrar('login', "nuevo {$n}");
        }
    }


    public function test_archivar_saca_lo_viejo_de_la_base_y_la_cadena_sigue_valida(): void
    {
        $this->conViejos();
        $this->assertTrue(Auditoria::verificarCadena()['ok']);

        $this->assertEquals(0, Artisan::call('auditoria:archivar', ['--dias' => 365, '--si' => true]));

        // quedan los 6 nuevos + el aviso del archivado
        $this->assertEquals(0, Auditoria::where('descripcion', 'like', 'viejo%')->count());
        $this->assertEquals(6, Auditoria::where('descripcion', 'like', 'nuevo%')->count());

        $r = Auditoria::verificarCadena();
        $this->assertTrue($r['ok'], implode(' | ', $r['problemas']));

        // el archivo tiene las 4 filas viejas y su hash coincide
        $archivo = \App\Models\AuditoriaArchivo::first();
        $this->assertEquals(4, $archivo->cantidad);

        $contenido = gzdecode(\Illuminate\Support\Facades\Storage::disk('backups')->get($archivo->archivo));
        $this->assertEquals(4, count(array_filter(explode("\n", $contenido))));
        $this->assertStringContainsString('viejo 1', $contenido);
        $this->assertEquals(1, Auditoria::where('evento', 'auditoria_archivada')->count());
    }


    public function test_si_falta_el_archivo_de_auditoria_se_detecta(): void
    {
        $this->conViejos();
        Artisan::call('auditoria:archivar', ['--dias' => 365, '--si' => true]);

        $archivo = \App\Models\AuditoriaArchivo::first();
        \Illuminate\Support\Facades\Storage::disk('backups')->delete($archivo->archivo);

        $r = Auditoria::verificarCadena();

        $this->assertFalse($r['ok']);
        $this->assertStringContainsString('No se encuentra el archivo', implode(' ', $r['problemas']));
    }


    public function test_si_cambian_el_archivo_de_auditoria_se_detecta(): void
    {
        $this->conViejos();
        Artisan::call('auditoria:archivar', ['--dias' => 365, '--si' => true]);

        $archivo = \App\Models\AuditoriaArchivo::first();
        \Illuminate\Support\Facades\Storage::disk('backups')->put($archivo->archivo, gzencode("falso\n"));

        $this->assertStringContainsString('cambió', implode(' ', Auditoria::verificarCadena()['problemas']));
    }


    public function test_borrar_registros_despues_de_archivar_sin_el_respaldo_se_detecta(): void
    {
        $this->conViejos();
        Artisan::call('auditoria:archivar', ['--dias' => 365, '--si' => true]);

        // sacan tambien el renglon que respalda el corte
        DB::connection('historico')->table('auditoria_archivos')->delete();

        $this->assertStringContainsString('no hay archivo que los respalde', implode(' ', Auditoria::verificarCadena()['problemas']));
    }


    public function test_no_archiva_si_la_cadena_esta_rota(): void
    {
        $this->conViejos();

        $this->tabla()->orderBy('id')->limit(1)->update(['descripcion' => 'tocado']);

        $this->assertEquals(1, Artisan::call('auditoria:archivar', ['--dias' => 365, '--si' => true]));
        $this->assertEquals(0, \App\Models\AuditoriaArchivo::count());
    }


    public function test_sin_registros_viejos_no_hace_nada(): void
    {
        $this->assertEquals(0, Artisan::call('auditoria:archivar', ['--dias' => 365, '--si' => true]));
        $this->assertEquals(0, \App\Models\AuditoriaArchivo::count());
    }
}

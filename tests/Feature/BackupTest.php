<?php

namespace Tests\Feature;

use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\Persona;
use App\Services\ArchivoSeguro;
use App\Services\CopiaDeSeguridad;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;


/*
 * usa bases sqlite en archivos temporales (no la de memoria) para poder
 * probar de verdad el backup y la restauracion
 */
class BackupTest extends TestCase
{
    private string $tmp;
    private string $documento;


    protected function setUp(): void
    {
        parent::setUp();

        $this->tmp = sys_get_temp_dir() . '/bk_' . uniqid();
        mkdir($this->tmp . '/backups', 0777, true);

        config([
            'database.connections.principal_tmp' => [
                'driver' => 'sqlite', 'database' => $this->tmp . '/principal.sqlite',
                'prefix' => '', 'foreign_key_constraints' => false,
            ],
            'database.connections.historico.database' => $this->tmp . '/historico.sqlite',
            'database.default' => 'principal_tmp',
            'filesystems.disks.backups.root' => $this->tmp . '/backups',
            'sistema.backup.discos' => ['backups'],
            'sistema.backup.password' => null,
        ]);

        touch($this->tmp . '/principal.sqlite');
        touch($this->tmp . '/historico.sqlite');
        DB::purge('principal_tmp');
        DB::purge('historico');

        Artisan::call('migrate', ['--force' => true]);

        // un documento cifrado de verdad en el disco privado
        $this->documento = 'documentos/zz_prueba_' . uniqid() . '.pdf';
        Storage::disk('local')->put($this->documento, ArchivoSeguro::encriptar('%PDF-1.4 contenido de prueba %%EOF'));
    }


    protected function tearDown(): void
    {
        Storage::disk('local')->delete($this->documento);
        File::deleteDirectory($this->tmp);
        File::deleteDirectory(storage_path('app/backup-temp'));

        parent::tearDown();
    }


    private function persona(string $nombre = 'Ana'): Persona
    {
        return Persona::create([
            'nombre' => $nombre, 'apellido' => 'Lopez', 'dni' => random_int(10000000, 99999999),
            'cuil' => random_int(20000000000, 29999999999), 'fecha_de_nacimiento' => '1990-01-01',
        ]);
    }


    public function test_el_backup_se_crea_y_pasa_la_verificacion(): void
    {
        $this->persona();

        $copia = app(CopiaDeSeguridad::class);
        $info = $copia->crear('completo');

        $this->assertTrue(Storage::disk('backups')->exists($info['nombre']));
        $this->assertTrue(Storage::disk('backups')->exists($info['nombre'] . '.sha256'));
        $this->assertTrue(Storage::disk('backups')->exists($info['nombre'] . '.json'));
        $this->assertTrue($copia->hashCorrecto($info['nombre']));

        $local = $copia->rutaLocal($info['nombre']);
        $resultado = $copia->verificar($local);
        @unlink($local);

        $this->assertTrue($resultado['ok'], implode(' | ', $resultado['errores']));

        $m = $resultado['manifiesto'];
        $this->assertEquals(1, $m['bases']['principal_tmp']['tablas']['personas']);
        $this->assertArrayHasKey('auditorias', $m['bases']['historico']['tablas']);
        $this->assertArrayHasKey('h_personas', $m['bases']['historico']['tablas']);
        $this->assertArrayHasKey('archivos/private/' . $this->documento, $m['archivos']);
        $this->assertArrayHasKey('db/principal_tmp.sqlite', $m['archivos']);
        $this->assertArrayHasKey('db/historico.sqlite', $m['archivos']);
        $this->assertEquals(64, strlen($m['archivos']['db/historico.sqlite']));

        $this->assertEquals(1, Auditoria::where('evento', 'backup_ok')->count());
    }


    public function test_el_comando_hace_el_backup(): void
    {
        $this->assertEquals(0, Artisan::call('sistema:backup', ['--tipo' => 'db']));

        $lista = app(CopiaDeSeguridad::class)->listar();

        $this->assertCount(1, $lista);
        $this->assertEquals('db', $lista[0]['tipo']);
    }


    public function test_se_detecta_un_backup_alterado(): void
    {
        $copia = app(CopiaDeSeguridad::class);
        $info = $copia->crear('completo');

        $ruta = $this->tmp . '/backups/' . $info['nombre'];
        $bytes = file_get_contents($ruta);
        $bytes[(int) (strlen($bytes) / 2)] = $bytes[(int) (strlen($bytes) / 2)] ^ "\xFF";
        file_put_contents($ruta, $bytes);

        $this->assertFalse($copia->hashCorrecto($info['nombre']));

        $this->assertEquals(1, Artisan::call('sistema:backup-verificar', ['archivo' => $info['nombre']]));
        $this->assertEquals(1, Auditoria::where('evento', 'backup_fallo')->count());
        $this->assertEquals(1, Incidente::where('tipo', 'backup_fallido')->count());
    }


    public function test_con_clave_el_zip_va_cifrado_e_incluye_el_env(): void
    {
        config(['sistema.backup.password' => 'clave-de-prueba-123']);

        $copia = app(CopiaDeSeguridad::class);
        $info = $copia->crear('completo');

        $this->assertTrue($info['cifrado']);

        // sin la clave no se puede leer ni el manifiesto
        $zip = new \ZipArchive();
        $zip->open($this->tmp . '/backups/' . $info['nombre']);
        $this->assertFalse($zip->getFromName('manifest.json'));
        $this->assertFalse($zip->getFromName('db/principal_tmp.sqlite'));
        $zip->close();

        // con la clave si
        $local = $copia->rutaLocal($info['nombre']);
        $resultado = $copia->verificar($local);
        @unlink($local);

        $this->assertTrue($resultado['ok'], implode(' | ', $resultado['errores']));
        $this->assertTrue($resultado['manifiesto']['incluye_env']);

        // con otra clave no
        config(['sistema.backup.password' => 'otra-clave']);
        $local = $copia->rutaLocal($info['nombre']);
        $this->assertFalse($copia->verificar($local)['ok']);
        @unlink($local);
    }


    public function test_sin_clave_no_se_incluye_el_env(): void
    {
        $info = app(CopiaDeSeguridad::class)->crear('db');

        $local = app(CopiaDeSeguridad::class)->rutaLocal($info['nombre']);
        $m = app(CopiaDeSeguridad::class)->verificar($local)['manifiesto'];
        @unlink($local);

        $this->assertFalse($m['incluye_env']);
        $this->assertArrayNotHasKey('sistema/.env', $m['archivos']);
    }


    public function test_restaurar_devuelve_bases_y_documentos(): void
    {
        $persona = $this->persona('Original');
        $persona->update(['nombre' => 'Cambiada']);

        $copia = app(CopiaDeSeguridad::class);
        $info = $copia->crear('completo');

        // despues del backup pasa un desastre
        Persona::query()->forceDelete();
        Storage::disk('local')->delete($this->documento);
        $this->assertEquals(0, Persona::withTrashed()->count());

        $hecho = $copia->restaurar($info['nombre']);

        DB::purge('principal_tmp');
        DB::purge('historico');

        $this->assertNotEmpty($hecho);
        $this->assertEquals('Cambiada', Persona::first()->nombre);
        $this->assertTrue(Storage::disk('local')->exists($this->documento));
        $this->assertSame('%PDF-1.4 contenido de prueba %%EOF', ArchivoSeguro::leer($this->documento));

        // el historico tambien volvio
        $this->assertEquals('Original', DB::connection('historico')->table('h_personas')->first()->nombre);

        // y antes de restaurar se hizo otro backup de seguridad
        $this->assertTrue(collect($copia->listar())->contains(fn ($b) => $b['tipo'] === 'prerestauracion'));
        $this->assertEquals(1, Auditoria::where('evento', 'backup_restaurado')->count());
    }


    public function test_no_restaura_un_backup_roto(): void
    {
        $copia = app(CopiaDeSeguridad::class);
        $info = $copia->crear('db');

        file_put_contents($this->tmp . '/backups/' . $info['nombre'], 'esto no es un zip');

        $this->expectException(\RuntimeException::class);
        $copia->restaurar($info['nombre']);
    }


    public function test_si_falla_el_backup_queda_registrado_como_incidente_critico(): void
    {
        config(['sistema.backup.discos' => ['disco_que_no_existe']]);

        try {
            app(CopiaDeSeguridad::class)->crear('db');
            $this->fail('tendria que haber fallado');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $e);
        }

        $this->assertEquals(1, Auditoria::where('evento', 'backup_fallo')->where('severidad', 'critica')->count());
        $this->assertEquals('critica', Incidente::where('tipo', 'backup_fallido')->first()->severidad);
    }


    public function test_sin_backups_la_verificacion_avisa(): void
    {
        $this->assertEquals(1, Artisan::call('sistema:backup-verificar'));
        $this->assertEquals(1, Incidente::where('tipo', 'backup_desactualizado')->count());
    }


    public function test_retencion_conserva_el_mas_nuevo_y_recorta_los_viejos(): void
    {
        Carbon::setTestNow('2026-10-13 12:00:00');

        $lista = [];

        // 4 backups por dia durante 120 dias (completos) y uno viejo de otro tipo
        foreach (range(0, 119) as $dia) {
            foreach ([0, 6, 12, 18] as $hora) {
                $f = now()->subDays($dia)->startOfDay()->addHours($hora);
                $lista[] = ['nombre' => 'completo_' . $f->format('Ymd_His') . '.zip', 'tipo' => 'completo', 'fecha' => $f, 'bytes' => 1, 'tiene_hash' => true];
            }
        }
        $lista[] = ['nombre' => 'db_20260101_000000.zip', 'tipo' => 'db', 'fecha' => Carbon::parse('2026-01-01'), 'bytes' => 1, 'tiene_hash' => true];

        $borrar = app(CopiaDeSeguridad::class)->aBorrar($lista, now());
        $quedan = collect($lista)->pluck('nombre')->diff($borrar);

        // el mas nuevo de cada tipo siempre queda
        $this->assertTrue($quedan->contains('db_20260101_000000.zip'));
        $this->assertTrue($quedan->contains('completo_20261013_060000.zip') || $quedan->contains('completo_20261013_000000.zip'));

        // ultimos 7 dias: se conservan todos
        $esReciente = fn ($n) => str_starts_with($n, 'completo_') && Carbon::createFromFormat('Ymd_His', substr($n, 9, 15))->diffInDays(now()) < 7;
        $this->assertGreaterThanOrEqual(28, collect($lista)->pluck('nombre')->filter($esReciente)->count());
        $this->assertSame(collect($lista)->pluck('nombre')->filter($esReciente)->count(), $quedan->filter($esReciente)->count());

        // se borra bastante y no se queda todo
        $this->assertGreaterThan(300, count($borrar));
        $this->assertLessThan(count($lista), $quedan->count());

        // entre 30 dias y 12 semanas queda como mucho uno por semana
        $porSemana = $quedan->filter(function ($n) {
            if (! str_starts_with($n, 'completo_')) return false;
            $d = Carbon::createFromFormat('Ymd_His', substr($n, 9, 15))->diffInDays(now());
            return $d >= 30 && $d < 84;
        })->groupBy(fn ($n) => Carbon::createFromFormat('Ymd_His', substr($n, 9, 15))->format('o-W'));
        $this->assertTrue($porSemana->every(fn ($g) => $g->count() === 1));

        Carbon::setTestNow();
    }
}

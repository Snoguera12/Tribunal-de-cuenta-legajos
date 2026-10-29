<?php

namespace Tests\Feature;

use App\Models\AntecedenteLaboral;
use App\Models\Documento;
use App\Models\Familiar;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\Titulo;
use App\Models\User;
use App\Services\ArchivoSeguro;
use App\Services\GeneradorDatosPrueba;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;


class DatosPruebaTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }


    private function poblar(int $personas = 60, int $documentos = 10): array
    {
        return (new GeneradorDatosPrueba($personas, $documentos, 'ClaveDePrueba1', pendientesLicencia: 0))->ejecutar();
    }


    public function test_crea_la_cantidad_y_los_roles_pedidos(): void
    {
        // 100 agentes, de los cuales solo 27 tienen usuario: el resto son agentes sin cuenta
        (new GeneradorDatosPrueba(100, 2, 'ClaveDePrueba1', pendientesLicencia: 0, empleados: 20, rrhh: 3, funcionarios: 2))->ejecutar();

        $this->assertEquals(100, Persona::count());
        $this->assertEquals(27, User::count());
        $this->assertEquals(73, Persona::doesntHave('usuario')->count());
        $this->assertEquals(2, User::where('rol', 'administrador')->count());
        $this->assertEquals(3, User::where('rol', 'rrhh')->count());
        $this->assertEquals(2, User::where('rol', 'funcionario')->count());
        $this->assertEquals(20, User::where('rol', 'empleado')->count());
    }


    public function test_los_valores_pedidos_son_500_agentes_con_222_usuarios(): void
    {
        $generador = new GeneradorDatosPrueba(personas: 500, documentos: 0, password: 'ClaveDePrueba1', conArchivos: false);
        $generador->ejecutar();

        $this->assertEquals(500, Persona::count());
        $this->assertEquals(222, User::count());
        $this->assertEquals(278, Persona::doesntHave('usuario')->count());
        $this->assertEquals(2, User::where('rol', 'administrador')->count());
        $this->assertEquals(10, User::where('rol', 'rrhh')->count());
        $this->assertEquals(10, User::where('rol', 'funcionario')->count());
        $this->assertEquals(200, User::where('rol', 'empleado')->count());
        $this->assertCount(222, $generador->cuentas());
    }


    public function test_hay_un_administrador_del_sector_tic_y_otro_de_rrhh_en_su_area(): void
    {
        $this->poblar(30);

        $tic = User::where('email', 'admin.tic@prueba.tribunal.test')->first();
        $rrhh = User::where('email', 'admin.rrhh@prueba.tribunal.test')->first();

        $this->assertTrue($tic->isAdminTic());
        $this->assertTrue($rrhh->isAdminRrhh());
        $this->assertSame('Computos', $tic->persona->legajos()->first()->area->nombre);
        $this->assertSame('Personal', $rrhh->persona->legajos()->first()->area->nombre);
        $this->assertSame(2, User::where('rol', 'administrador')->whereNotNull('sector')->count());
    }


    public function test_se_usan_las_areas_cargos_y_categorias_reales_del_tribunal(): void
    {
        $this->poblar(120);

        $this->assertEqualsCanonicalizing(\Database\Seeders\CatalogosSeeder::AREAS, \App\Models\Area::pluck('nombre')->all());
        $this->assertEqualsCanonicalizing(\Database\Seeders\CatalogosSeeder::CARGOS, \App\Models\Cargo::pluck('nombre')->all());
        $this->assertEqualsCanonicalizing(array_keys(\Database\Seeders\CatalogosSeeder::CATEGORIAS), \App\Models\Categoria::pluck('nombre')->all());

        // ningun legajo usa un cargo que no existe en el catalogo
        $this->assertSame(0, Legajo::whereNull('cargo_id')->orWhereNull('area_id')->orWhereNull('categoria_id')->count());
    }


    public function test_cada_usuario_recibe_su_propia_clave_si_no_se_pide_una_comun(): void
    {
        $generador = new GeneradorDatosPrueba(personas: 40, documentos: 0, conArchivos: false, empleados: 20, rrhh: 3, funcionarios: 2);
        $generador->ejecutar();

        $cuentas = $generador->cuentas();
        $claves = array_column($cuentas, 'clave');

        $this->assertCount(27, $cuentas);
        $this->assertCount(27, array_unique($claves), 'cada clave es distinta');

        foreach ($cuentas as $c) {
            $this->assertTrue(Hash::check($c['clave'], User::where('email', $c['correo'])->value('password')), $c['correo']);
            $this->assertGreaterThanOrEqual(12, strlen($c['clave']));
        }
    }


    public function test_los_usuarios_pueden_ingresar_con_la_clave_y_no_tienen_que_cambiarla(): void
    {
        $this->poblar(20);

        $admin = User::where('email', 'admin.tic@prueba.tribunal.test')->first();

        $this->assertNotNull($admin);
        $this->assertTrue(Hash::check('ClaveDePrueba1', $admin->password));
        $this->assertFalse($admin->debe_cambiar_clave);
        $this->assertNotNull(User::where('email', 'rrhh1@prueba.tribunal.test')->first());
        $this->assertNotNull(User::where('email', 'funcionario5@prueba.tribunal.test')->first());
    }


    public function test_cada_persona_tiene_10_documentos_y_cada_archivo_se_abre_y_es_valido(): void
    {
        $this->poblar(15);

        $this->assertEquals(150, Documento::count());

        foreach (Persona::all() as $persona) {
            $documentos = Documento::whereIn('legajo_id', Legajo::where('persona_id', $persona->id)->pluck('id'))->get();
            $this->assertCount(10, $documentos, "persona {$persona->id}");
        }

        foreach (Documento::limit(40)->get() as $documento) {

            $contenido = ArchivoSeguro::leer($documento->ruta);

            $this->assertNotNull($contenido, $documento->ruta);
            $this->assertTrue(ArchivoSeguro::esPdfOJpeg($contenido));
            $this->assertStringEndsWith($documento->tipodoc->value === 0 || $documento->tipodoc->value === 10 ? '.jpg' : '.pdf', $documento->ruta);

            // en disco esta cifrado, no se lee a simple vista
            $this->assertStringNotContainsString('%PDF', Storage::disk('local')->get($documento->ruta));
        }
    }


    public function test_dos_archivos_nunca_son_iguales_entre_personas(): void
    {
        $this->poblar(20);

        $this->assertEquals(Documento::count(), Documento::distinct('ruta')->count('ruta'));
    }


    public function test_no_repite_dni_cuil_ni_correos_y_el_cuil_es_valido(): void
    {
        $this->poblar(80);

        $this->assertEquals(80, Persona::distinct('dni')->count('dni'));
        $this->assertEquals(80, Persona::distinct('cuil')->count('cuil'));
        $this->assertEquals(60 + 20, User::distinct('email')->count('email'));
        $this->assertEquals(0, Familiar::whereIn('dni', Persona::pluck('dni'))->count(), 'un familiar no comparte DNI con un agente');

        foreach (Persona::all() as $p) {
            $genero = ($p->genero?->value ?? $p->genero) === 0 ? 'F' : 'M';
            $this->assertEquals(GeneradorDatosPrueba::cuil($p->dni, $genero), (int) $p->cuil);
            $this->assertLessThanOrEqual(50, mb_strlen($p->email));
        }
    }


    public function test_el_dni_corresponde_al_anio_de_nacimiento(): void
    {
        $this->poblar(150);

        foreach (Persona::all() as $p) {
            $anio = Carbon::parse($p->fecha_de_nacimiento)->year;

            // 1960 ronda los 13 millones y 2000 los 42 millones
            $esperado = 13_500_000 + (42_000_000 - 13_500_000) * ($anio - 1960) / 40;
            $this->assertEqualsWithDelta($esperado, $p->dni, 5_500_000, "nacido en {$anio} con DNI {$p->dni}");
        }
    }


    public function test_el_cuil_usa_el_algoritmo_real(): void
    {
        // ejemplo conocido: DNI 12345678 hombre -> 20-12345678-6
        $this->assertEquals(20123456786, GeneradorDatosPrueba::cuil(12345678, 'M'));
        $this->assertEquals(11, strlen((string) GeneradorDatosPrueba::cuil(30111222, 'F')));
    }


    public function test_los_datos_son_coherentes_entre_si(): void
    {
        $this->poblar(120);

        foreach (Persona::all() as $p) {
            $edad = Carbon::parse($p->fecha_de_nacimiento)->age;
            $this->assertGreaterThanOrEqual(20, $edad);
            $this->assertLessThanOrEqual(65, $edad);

            // trabajo desde los 18 como minimo
            foreach (Legajo::where('persona_id', $p->id)->get() as $legajo) {
                $this->assertGreaterThanOrEqual(18, Carbon::parse($p->fecha_de_nacimiento)->diffInYears($legajo->fecha_de_ingreso));
            }
        }

        // los hijos son bastante menores que el agente y los padres mayores
        foreach (Familiar::with('persona')->get() as $f) {
            $edadAgente = Carbon::parse($f->persona->fecha_de_nacimiento);
            $edadFamiliar = Carbon::parse($f->fecha_de_nacimiento);
            $valor = $f->parentesco->value;

            if ($valor === 2) {
                $this->assertGreaterThanOrEqual(15, $edadFamiliar->diffInYears($edadAgente, false) * -1 + 0 > 0 ? $edadAgente->diffInYears($edadFamiliar) : 15);
                $this->assertTrue($edadFamiliar->gt($edadAgente), 'el hijo nació después que el agente');
            }

            if ($valor === 1) {
                $this->assertTrue($edadFamiliar->lt($edadAgente), 'el padre nació antes que el agente');
            }
        }

        // hay variedad real
        $this->assertGreaterThan(0, Familiar::where('parentesco', 0)->count());
        $this->assertGreaterThan(0, Familiar::where('parentesco', 2)->count());
        $this->assertGreaterThan(0, Familiar::where('vive', 1)->count());
        $this->assertGreaterThan(0, Titulo::count());
        $this->assertGreaterThan(0, AntecedenteLaboral::count());
    }


    public function test_hay_bajas_y_esos_usuarios_quedan_deshabilitados_al_sincronizar(): void
    {
        $this->poblar(250);

        $this->assertGreaterThan(0, \App\Models\Historialbaja::count());

        Artisan::call('usuarios:sincronizar-habilitacion');

        $deshabilitados = User::where('habilitado', false)->get();
        $this->assertGreaterThan(0, $deshabilitados->count());

        foreach ($deshabilitados as $u) {
            $this->assertEquals(0, Legajo::where('persona_id', $u->persona_id)->where('estado', true)->count(), 'solo sin legajo de alta');
            $this->assertNotEquals('administrador', $u->rol);
        }
    }


    public function test_el_comando_carga_todo_y_se_niega_a_cargar_dos_veces(): void
    {
        $this->assertEquals(0, Artisan::call('datos:poblar', ['--personas' => 30, '--licencias' => 0, '--password' => 'ClaveDePrueba1', '--si' => true]));
        $this->assertEquals(30, Persona::count());
        $this->assertEquals(300, Documento::count());

        // se copio al historico
        $this->assertGreaterThan(0, \Illuminate\Support\Facades\DB::connection('historico')->table('h_personas')->count());

        $this->assertEquals(1, Artisan::call('datos:poblar', ['--personas' => 30, '--si' => true]));
        $this->assertEquals(30, Persona::count());

        // con --agregar suma sin chocar
        $this->assertEquals(0, Artisan::call('datos:poblar', ['--personas' => 20, '--agregar' => true, '--licencias' => 0, '--password' => 'ClaveDePrueba1', '--si' => true]));
        $this->assertEquals(50, Persona::count());
    }


    public function test_sin_archivos_no_crea_documentos(): void
    {
        Artisan::call('datos:poblar', ['--personas' => 12, '--sin-archivos' => true, '--password' => 'ClaveDePrueba1', '--si' => true]);

        $this->assertEquals(12, Persona::count());
        $this->assertEquals(0, Documento::count());
    }


    public function test_se_puede_crear_el_primer_administrador_para_empezar_de_cero(): void
    {
        $this->assertEquals(0, Artisan::call('sistema:crear-admin', ['email' => 'Jefe@Tribunal.test', '--nombre' => 'Jefe', '--password' => 'Provisoria123']));

        $admin = User::where('email', 'jefe@tribunal.test')->first();

        $this->assertEquals('administrador', $admin->rol);
        $this->assertTrue($admin->debe_cambiar_clave);
        $this->assertTrue(Hash::check('Provisoria123', $admin->password));

        $this->assertEquals(1, Artisan::call('sistema:crear-admin', ['email' => 'jefe@tribunal.test']));
        $this->assertEquals(1, Artisan::call('sistema:crear-admin', ['email' => 'no-es-un-correo']));
    }


    public function test_el_comando_guarda_un_txt_con_las_claves_de_todos_y_el_token(): void
    {
        $archivo = sys_get_temp_dir() . '/claves_' . uniqid() . '.txt';

        $this->assertEquals(0, Artisan::call('datos:poblar', [
            '--personas' => 40, '--empleados' => 15, '--rrhh' => 3, '--funcionarios' => 2, '--documentos' => 1,
            '--licencias' => 3, '--archivo' => $archivo, '--si' => true,
        ]));

        $txt = file_get_contents($archivo);
        @unlink($archivo);

        // 2 administradores + 3 rrhh + 2 funcionarios + 15 empleados
        $this->assertEquals(22, User::count());

        foreach (User::all() as $usuario) {
            $this->assertStringContainsString($usuario->email, $txt);
        }

        $this->assertStringContainsString('admin.tic@prueba.tribunal.test', $txt);
        $this->assertStringContainsString('admin.rrhh@prueba.tribunal.test', $txt);
        $this->assertStringContainsString('Token:', $txt);
        $this->assertStringContainsString('/api/licencias/documentos', $txt);

        // el token del txt es el real: funciona
        preg_match('/Token:\s+(\S+)/', $txt, $m);
        $this->assertNotNull(\Laravel\Sanctum\PersonalAccessToken::findToken($m[1]));
    }


    public function test_deja_pdf_de_licencias_pendientes_de_asignar(): void
    {
        $this->assertEquals(0, Artisan::call('datos:poblar', ['--personas' => 20, '--documentos' => 1, '--licencias' => 4, '--password' => 'ClaveDePrueba1', '--si' => true]));

        $pendientes = Documento::pendientes()->where('origen', 'api_licencias')->get();

        $this->assertCount(4, $pendientes);

        foreach ($pendientes as $d) {
            $this->assertNull($d->legajo_id);
            $this->assertNotNull(ArchivoSeguro::leer($d->ruta));
        }

        // cada documento con legajo tambien esta en las asignaciones, sin repetirse
        $conLegajo = Documento::whereNotNull('legajo_id')->count();
        $this->assertSame($conLegajo, \Illuminate\Support\Facades\DB::table('documento_legajo')->count());
    }


    public function test_por_defecto_la_carga_queda_anotada_en_la_auditoria_y_el_historico(): void
    {
        $this->assertEquals(0, Artisan::call('datos:poblar', ['--personas' => 20, '--documentos' => 0, '--licencias' => 0, '--password' => 'ClaveDePrueba1', '--si' => true]));

        $this->assertGreaterThan(0, \App\Models\Auditoria::where('evento', 'importacion')->count());
        $this->assertTrue(\App\Models\Auditoria::$activo);
    }


    public function test_con_sin_auditoria_la_auditoria_y_el_historico_quedan_vacios(): void
    {
        $this->assertEquals(0, Artisan::call('datos:poblar', ['--personas' => 30, '--documentos' => 1, '--licencias' => 2, '--sin-auditoria' => true, '--password' => 'ClaveDePrueba1', '--si' => true]));

        // los datos estan, pero no hay nada anotado
        $this->assertEquals(30, Persona::count());
        $this->assertEquals(0, \App\Models\Auditoria::count());
        $this->assertEquals(0, \Illuminate\Support\Facades\DB::connection('historico')->table('h_personas')->count());

        // y la auditoria vuelve a funcionar despues (no queda apagada)
        $this->assertTrue(\App\Models\Auditoria::$activo);
        \App\Models\Auditoria::registrar('login', 'prueba');
        $this->assertEquals(1, \App\Models\Auditoria::count());
    }
}

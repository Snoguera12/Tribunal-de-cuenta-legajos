<?php

namespace Tests\Feature;

use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Widgets\UltimosAgentesWidget;
use App\Models\ActividadReciente;
use App\Models\ApiClient;
use App\Models\Area;
use App\Models\Auditoria;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use App\Services\Antivirus;
use App\Services\MiniPdf;
use Filament\Facades\Filament;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;


// permisos por token, DNI sugerido, documento compartido, diagnostico, limpieza, antivirus, un admin por sector
class SeguridadYMantenimientoTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];

    private int $n = 0;


    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Filament::setCurrentPanel('legajos');
    }


    private function usuario(string $rol, array $extra = []): User
    {
        $this->n++;

        return User::create(['name' => "{$rol} {$this->n}", 'email' => "{$rol}{$this->n}@test.com", 'password' => 'x12345678', 'rol' => $rol, ...$extra]);
    }


    private function legajo(array $persona = []): Legajo
    {
        $this->n++;

        $p = Persona::create([...['nombre' => "P{$this->n}", 'apellido' => 'Test', 'dni' => 30000000 + $this->n, 'cuil' => 20000000000 + $this->n,
            'email' => "p{$this->n}@test.com", 'genero' => 0, 'estado_civil' => 0, 'fecha_de_nacimiento' => '1990-01-01'], ...$persona]);

        return Legajo::create(['num_legajo' => 2000 + $this->n, 'estado' => true, 'fecha_de_ingreso' => '2020-01-01', 'tipo_contrato' => 2, 'persona_id' => $p->id,
            'categoria_id' => Categoria::firstOrCreate(['nombre' => 'CAT 1'], ['descripcion' => 'x'])->id,
            'cargo_id' => Cargo::firstOrCreate(['nombre' => 'Administrativo'])->id,
            'area_id' => Area::firstOrCreate(['nombre' => 'Personal'])->id]);
    }


    private function tokenDe(array $permisos): string
    {
        $cliente = ApiClient::create(['nombre' => 'Cliente ' . ++$this->n, 'is_active' => true, 'is_token' => true]);

        return $cliente->createToken('t', $permisos)->plainTextToken;
    }


    private function pdf(): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('licencia.pdf', MiniPdf::hoja('Licencia', [['Resolución', 10, false]]));
    }


    // en un pedido real cada token es un pedido nuevo; dentro de un test hay que olvidar al usuario del pedido anterior
    private function conToken(string $token): static
    {
        Auth::forgetGuards();

        return $this->withToken($token);
    }


    private function volverAlPanel(): void
    {
        $this->flushHeaders();
        Auth::forgetGuards();
        Auth::shouldUse('web');
    }


    // ---------------------------------------------------------- permisos por token

    public function test_cada_token_solo_hace_lo_que_se_le_dio(): void
    {
        $legajo = $this->legajo();
        $soloPersonas = $this->tokenDe(['personas:leer']);
        $soloLicencias = $this->tokenDe(['licencias:crear']);
        $todo = $this->tokenDe(['*']);   // los tokens viejos

        $this->conToken($soloPersonas)->getJson('/api/personas')->assertOk();
        $this->conToken($soloPersonas)->getJson('/api/legajos/por-dni/' . $legajo->persona->dni)->assertForbidden();
        $this->conToken($soloPersonas)->post('/api/licencias/documentos', ['archivo' => $this->pdf()], ['Accept' => 'application/json'])->assertForbidden();

        $this->conToken($soloLicencias)->getJson('/api/personas')->assertForbidden();
        $this->conToken($soloLicencias)->post('/api/licencias/documentos', ['archivo' => $this->pdf(), 'id_solicitud' => '1'], ['Accept' => 'application/json'])->assertStatus(201);

        // los tokens que ya existian (con *) siguen funcionando para todo
        $this->conToken($todo)->getJson('/api/personas')->assertOk();
        $this->conToken($todo)->getJson('/api/legajos/por-dni/' . $legajo->persona->dni)->assertOk();
    }


    public function test_el_administrador_elige_los_permisos_al_generar_el_token(): void
    {
        $this->actingAs($this->usuario('administrador'));
        $cliente = ApiClient::create(['nombre' => 'Otro sistema', 'is_active' => false, 'is_token' => false]);

        Livewire::test(\App\Filament\Resources\ApiClients\Pages\ListApiClients::class)
            ->callTableAction('generateToken', $cliente)   // sin tocar nada: vienen tildados todos los permisos
            ->assertHasNoTableActionErrors();

        $this->assertEqualsCanonicalizing(array_keys(ApiClient::HABILIDADES), $cliente->tokens()->first()->abilities);
    }


    public function test_solo_se_aceptan_los_permisos_que_existen(): void
    {
        $this->assertSame(['personas:leer'], ApiClient::habilidadesValidas(['personas:leer', 'permiso:inventado']));
        $this->assertSame(array_keys(ApiClient::HABILIDADES), ApiClient::habilidadesValidas(null));
        $this->assertSame([], ApiClient::habilidadesValidas([]));
    }


    // ---------------------------------------------------------- DNI sugerido

    public function test_el_dni_de_la_api_se_guarda_y_se_propone_al_asignar(): void
    {
        $legajo = $this->legajo(['dni' => 28123456]);
        $otro = $this->legajo();

        $token = $this->tokenDe(['licencias:crear']);
        $this->conToken($token)->post('/api/licencias/documentos', ['archivo' => $this->pdf(), 'id_solicitud' => '9', 'dni' => '28123456'], ['Accept' => 'application/json'])->assertStatus(201);
        $doc = Documento::first();

        $this->assertSame('28123456', $doc->dni_sugerido);
        $this->assertSame($legajo->id, $doc->legajoSugerido()->id);
        $this->assertNull($doc->legajo_id, 'sugerir no asigna: RRHH confirma');

        $this->volverAlPanel();
        $this->actingAs($this->usuario('rrhh'));

        Livewire::test(ListDocumentos::class)
            ->mountTableAction('asignar', $doc)
            ->assertTableActionDataSet(['destino' => 'uno', 'legajos' => [$legajo->id]]);

        // un DNI mal escrito se rechaza
        $this->volverAlPanel();
        $this->conToken($token)->post('/api/licencias/documentos', ['archivo' => $this->pdf(), 'id_solicitud' => '10', 'dni' => 'abc'], ['Accept' => 'application/json'])->assertStatus(422);
    }


    // ---------------------------------------------------------- documento compartido

    public function test_borrar_un_documento_compartido_desde_un_legajo_solo_lo_saca_de_ese_legajo(): void
    {
        [$a, $b, $c] = [$this->legajo(), $this->legajo(), $this->legajo()];
        $doc = Documento::create(['ruta' => 'documentos/x.pdf', 'descripcion' => 'Circular', 'tipodoc' => 0, 'legajo_id' => $a->id]);
        $doc->asignarA([$a->id, $b->id, $c->id]);
        $doc->legajo_id = $a->id;
        $doc->save();

        $doc->fresh()->delete();   // lo que hace el repetidor del legajo A

        $doc = Documento::find($doc->id);
        $this->assertNotNull($doc, 'sigue existiendo');
        $this->assertNull($doc->deleted_at);
        $this->assertEqualsCanonicalizing([$b->id, $c->id], $doc->legajos()->pluck('legajos.id')->all());
        $this->assertContains($doc->legajo_id, [$b->id, $c->id]);

        // cuando queda en uno solo, borrar si lo borra (papelera)
        $doc->asignarA([$b->id]);
        $doc->fresh()->delete();
        $this->assertSoftDeleted('documentos', ['id' => $doc->id]);
    }


    public function test_el_borrado_definitivo_sigue_funcionando_aunque_este_compartido(): void
    {
        [$a, $b] = [$this->legajo(), $this->legajo()];
        $doc = Documento::create(['ruta' => 'documentos/y.pdf', 'descripcion' => 'x', 'tipodoc' => 0, 'legajo_id' => $a->id]);
        $doc->asignarA([$a->id, $b->id]);

        $doc->fresh()->forceDelete();

        $this->assertNull(Documento::withTrashed()->find($doc->id));
    }


    // ---------------------------------------------------------- reiniciar, diagnostico, limpieza

    public function test_actividad_limpiar_borra_lo_viejo(): void
    {
        $rrhh = $this->usuario('rrhh');
        $viejo = ActividadReciente::create(['user_id' => $rrhh->id, 'tipo' => 'agente', 'registro_id' => 1, 'visto_en' => now()->subDays(200)]);
        ActividadReciente::where('id', $viejo->id)->update(['updated_at' => now()->subDays(200)]);
        $nuevo = ActividadReciente::create(['user_id' => $rrhh->id, 'tipo' => 'agente', 'registro_id' => 2, 'visto_en' => now()]);

        $this->assertSame(0, Artisan::call('actividad:limpiar', ['--dias' => 90]));

        $this->assertNull(ActividadReciente::find($viejo->id));
        $this->assertNotNull(ActividadReciente::find($nuevo->id));
    }


    public function test_el_administrador_puede_ver_lo_ultimo_de_todo_rrhh(): void
    {
        $uno = $this->usuario('rrhh');
        $dos = $this->usuario('rrhh');
        $admin = $this->usuario('administrador');
        ActividadReciente::create(['user_id' => $uno->id, 'tipo' => 'agente', 'registro_id' => $this->legajo()->persona_id, 'visto_en' => now()]);
        $deDos = ActividadReciente::create(['user_id' => $dos->id, 'tipo' => 'agente', 'registro_id' => $this->legajo()->persona_id, 'modificado_en' => now()]);

        $this->actingAs($admin);
        Livewire::test(UltimosAgentesWidget::class)->assertCountTableRecords(0);   // por defecto solo lo propio

        Livewire::test(UltimosAgentesWidget::class)
            ->filterTable('alcance', 'todos')
            ->assertCountTableRecords(2)
            ->assertCanSeeTableRecords([$deDos]);

        // un RRHH no tiene ese filtro: solo ve lo suyo
        $this->actingAs($uno);
        Livewire::test(UltimosAgentesWidget::class)->assertCountTableRecords(1);
    }


    public function test_el_diagnostico_marca_error_con_debug_en_produccion_y_claves_de_ejemplo(): void
    {
        $this->usuario('administrador', ['password' => 'Legajo2026!']);

        config(['app.env' => 'production', 'app.debug' => true]);
        $this->app['env'] = 'production';

        $this->assertSame(1, Artisan::call('sistema:diagnostico'));
        $salida = Artisan::output();
        $this->assertStringContainsString('APP_DEBUG', $salida);
        $this->assertStringContainsString('Claves conocidas', $salida);
        $this->assertStringContainsString('ERROR', $salida);

        config(['app.debug' => false]);
        $this->app['env'] = 'local';
        User::query()->delete();
        $codigo = Artisan::call('sistema:diagnostico');
        $errores = collect(explode("\n", Artisan::output()))->filter(fn ($l) => str_contains($l, '| ERROR'))->implode("\n");
        $this->assertSame(0, $codigo, "Errores: \n" . $errores);
    }


    // ---------------------------------------------------------- antivirus

    private function antivirusFalso(): void
    {
        $guion = sys_get_temp_dir() . '/av_falso_' . uniqid() . '.php';
        file_put_contents($guion, '<?php $c = file_get_contents($argv[1]); exit(str_contains($c, "VIRUS-DE-PRUEBA") ? 1 : (str_contains($c, "ROMPER-AV") ? 3 : 0));');
        config(['sistema.antivirus.activo' => true, 'sistema.antivirus.comando' => PHP_BINARY . ' ' . $guion]);
    }


    public function test_con_antivirus_activo_una_amenaza_se_rechaza_y_queda_anotada(): void
    {
        $this->antivirusFalso();
        $token = $this->tokenDe(['licencias:crear']);

        $limpio = $this->conToken($token)->post('/api/licencias/documentos', ['archivo' => $this->pdf(), 'id_solicitud' => '1'], ['Accept' => 'application/json']);
        $limpio->assertStatus(201);

        $infectado = UploadedFile::fake()->createWithContent('malo.pdf', MiniPdf::hoja('VIRUS-DE-PRUEBA', [['x', 10, false]]));
        $this->conToken($token)->post('/api/licencias/documentos', ['archivo' => $infectado, 'id_solicitud' => '2'], ['Accept' => 'application/json'])->assertStatus(422);

        $this->assertSame(1, Documento::count());
        $this->assertSame(1, Auditoria::where('evento', 'archivo_rechazado')->where('descripcion', 'like', '%antivirus%')->count());
    }


    public function test_si_el_antivirus_falla_se_acepta_o_se_rechaza_segun_la_configuracion(): void
    {
        $this->antivirusFalso();
        $token = $this->tokenDe(['licencias:crear']);
        $roto = fn (string $id) => UploadedFile::fake()->createWithContent("{$id}.pdf", MiniPdf::hoja('ROMPER-AV ' . $id, [['x', 10, false]]));

        config(['sistema.antivirus.si_falla' => 'permitir']);
        $this->conToken($token)->post('/api/licencias/documentos', ['archivo' => $roto('a'), 'id_solicitud' => '1'], ['Accept' => 'application/json'])->assertStatus(201);
        $this->assertSame(1, Auditoria::where('evento', 'antivirus_error')->count());

        config(['sistema.antivirus.si_falla' => 'rechazar']);
        $this->conToken($token)->post('/api/licencias/documentos', ['archivo' => $roto('b'), 'id_solicitud' => '2'], ['Accept' => 'application/json'])->assertStatus(422);
    }


    public function test_apagado_el_antivirus_no_se_ejecuta(): void
    {
        config(['sistema.antivirus.activo' => false]);

        $this->assertSame(Antivirus::APAGADO, Antivirus::escanear('/no/existe')[0]);
    }


    // ---------------------------------------------------------- un administrador por sector

    public function test_no_se_pueden_crear_dos_administradores_del_mismo_sector(): void
    {
        $this->usuario('administrador', ['sector' => 'tic']);
        $this->actingAs($this->usuario('administrador', ['sector' => 'rrhh']));

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Otro', 'email' => 'otro@test.com', 'password' => 'Clave12345', 'rol' => 'administrador', 'sector' => 'tic'])
            ->call('create')
            ->assertHasFormErrors(['sector']);

        $this->assertNull(User::where('email', 'otro@test.com')->first());
    }


    // ---------------------------------------------------------- robustez de la base

    public function test_conReintentos_repite_si_la_base_esta_ocupada_y_no_repite_otros_errores(): void
    {
        $veces = 0;
        $r = Auditoria::conReintentos(function () use (&$veces) {
            if (++$veces < 4) {
                throw new QueryException('historico', 'insert', [], new \Exception('SQLSTATE[HY000]: General error: 5 database is locked'));
            }

            return 'listo';
        });
        $this->assertSame('listo', $r);
        $this->assertSame(4, $veces);

        $veces = 0;
        try {
            Auditoria::conReintentos(function () use (&$veces) {
                $veces++;
                throw new QueryException('historico', 'insert', [], new \Exception('no such table: auditorias'));
            });
            $this->fail('tenia que salir el error');
        } catch (QueryException) {
            $this->assertSame(1, $veces, 'un error que no es de "ocupado" no se repite');
        }
    }


    public function test_sqlite_queda_en_wal_con_espera_para_varios_usuarios(): void
    {
        $this->assertSame('WAL', config('database.connections.sqlite.journal_mode'));
        $this->assertGreaterThanOrEqual(1000, config('database.connections.sqlite.busy_timeout'));
        $this->assertSame('WAL', config('database.connections.historico.journal_mode'));
        $this->assertGreaterThanOrEqual(1000, config('database.connections.historico.busy_timeout'));
    }


    public function test_las_migraciones_del_historico_se_pueden_repetir(): void
    {
        foreach (['2026_10_12_000000_create_auditorias_en_historico', '2026_10_12_000001_create_incidentes_en_historico', '2026_10_12_000007_create_auditoria_archivos_table'] as $nombre) {
            $migracion = require database_path("migrations/{$nombre}.php");

            // las tablas ya existen: no debe fallar (antes: "table auditorias already exists")
            $migracion->up();
            $migracion->up();
        }

        $this->assertTrue(true);
    }


    // si Filament cambia su vista del paginador hay que revisar nuestra copia en resources/views/vendor
    public function test_la_vista_de_paginacion_de_filament_no_cambio(): void
    {
        $this->assertSame(
            '5cecbbc4d25e3494033cd9c8ef52d062',
            md5_file(base_path('vendor/filament/support/resources/views/components/pagination/index.blade.php')),
            'Filament cambió su vista de paginación: revisá resources/views/vendor/filament/components/pagination/index.blade.php (es una copia con "ir a la página" y páginas vecinas) y actualizá esta huella.'
        );
    }
}

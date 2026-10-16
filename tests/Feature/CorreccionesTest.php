<?php

namespace Tests\Feature;

use App\Filament\Pages\MisDatosPersonales;
use App\Filament\Resources\ApiClients\Pages\ListApiClients;
use App\Filament\Resources\Personas\Pages\CreatePersona;
use App\Http\Controllers\Api\LegajoController;
use App\Http\Controllers\Api\PersonaController;
use App\Models\ApiClient;
use App\Models\Area;
use App\Models\Auditoria;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use App\Policies\UserPolicy;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Livewire\Livewire;
use Tests\TestCase;


class CorreccionesTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function usuario(string $rol): User
    {
        return User::create(['name' => $rol, 'email' => "{$rol}@test.com", 'password' => 'x12345678', 'rol' => $rol]);
    }


    private function persona(int $n = 1): Persona
    {
        return Persona::create(['nombre' => "P{$n}", 'apellido' => 'X', 'dni' => 30000000 + $n, 'cuil' => 20000000000 + $n, 'fecha_de_nacimiento' => '1990-01-01']);
    }


    // pedido como si lo hiciera un cliente de la api
    private function comoCliente($cliente, array $query = []): Request
    {
        $request = Request::create('/api/x', 'GET', $query);
        $request->setUserResolver(fn () => $cliente);

        return $request;
    }


    // ---------------------------------------------------------- api

    public function test_el_listado_de_personas_viene_paginado_y_con_tope(): void
    {
        $cliente = ApiClient::create(['nombre' => 'Sistema', 'is_active' => true, 'is_token' => true]);

        foreach (range(1, 120) as $n) {
            $this->persona($n);
        }

        $r = app(PersonaController::class)->index($this->comoCliente($cliente, ['por_pagina' => 5000]))->getData(true);

        $this->assertEquals(100, $r['por_pagina']);
        $this->assertEquals(120, $r['total']);
        $this->assertCount(100, $r['personas']);
        $this->assertTrue($r['is_active']);

        $r2 = app(PersonaController::class)->index($this->comoCliente($cliente, ['por_pagina' => 100, 'page' => 2]))->getData(true);
        $this->assertCount(20, $r2['personas']);
    }


    public function test_un_cliente_inactivo_o_un_usuario_comun_no_puede_usar_la_api(): void
    {
        $inactivo = ApiClient::create(['nombre' => 'Viejo', 'is_active' => false, 'is_token' => true]);

        $this->assertEquals(403, app(PersonaController::class)->index($this->comoCliente($inactivo))->status());
        $this->assertEquals(403, app(PersonaController::class)->index($this->comoCliente($this->usuario('administrador')))->status());
        $this->assertEquals(403, app(LegajoController::class)->show_dni($this->comoCliente($inactivo), '123')->status());
        $this->assertEquals(3, Auditoria::where('evento', 'api_denegado')->count());
    }


    public function test_consulta_por_dni_valida_responde_404_y_no_revienta(): void
    {
        $cliente = ApiClient::create(['nombre' => 'Sistema', 'is_active' => true, 'is_token' => true]);
        $controlador = app(LegajoController::class);

        $this->assertEquals(422, $controlador->show_dni($this->comoCliente($cliente), 'abc')->status());
        $this->assertEquals(422, $controlador->show_dni($this->comoCliente($cliente), "1' OR '1'='1")->status());
        $this->assertEquals(404, $controlador->show_dni($this->comoCliente($cliente), '99999999')->status());

        $this->assertEquals('sin resultados', Auditoria::where('evento', 'api_consulta')->first()->extra['resultado']);
    }


    public function test_consulta_por_dni_muestra_la_baja_del_legajo(): void
    {
        $cliente = ApiClient::create(['nombre' => 'Sistema', 'is_active' => true, 'is_token' => true]);
        $persona = $this->persona();

        $area = Area::create(['nombre' => 'Area']);
        $cargo = Cargo::create(['nombre' => 'Cargo']);
        $categoria = Categoria::create(['nombre' => 'Cat', 'descripcion' => 'd']);

        $legajo = Legajo::create([
            'num_legajo' => 10, 'estado' => true, 'fecha_de_ingreso' => now(), 'tipo_contrato' => 1,
            'persona_id' => $persona->id, 'categoria_id' => $categoria->id, 'cargo_id' => $cargo->id, 'area_id' => $area->id,
        ]);

        $activo = app(LegajoController::class)->show_dni($this->comoCliente($cliente), (string) $persona->dni)->getData(true);
        $this->assertNull($activo['legajo'][0]['baja']);

        $legajo->update(['estado' => false]);
        \App\Models\Historialbaja::create(['legajo_id' => $legajo->id, 'motivo' => 1, 'fecha_baja' => now(), 'user_id' => $this->usuario('administrador')->id]);

        $baja = app(LegajoController::class)->show_dni($this->comoCliente($cliente), (string) $persona->dni)->getData(true);
        $this->assertNotNull($baja['legajo'][0]['baja']);
    }


    // ---------------------------------------------------------- tokens

    public function test_borrar_el_token_desactiva_al_cliente_y_no_lo_elimina(): void
    {
        Filament::setCurrentPanel('legajos');
        $this->actingAs($this->usuario('administrador'));

        $cliente = ApiClient::create(['nombre' => 'Sistema', 'is_active' => false, 'is_token' => false]);

        Livewire::test(ListApiClients::class)->callTableAction('generateToken', $cliente);

        $cliente->refresh();
        $this->assertTrue((bool) $cliente->is_active);
        $this->assertTrue((bool) $cliente->is_token);
        $this->assertStringContainsString('Sistema', Auditoria::where('evento', 'token_generado')->first()->descripcion);

        Livewire::test(ListApiClients::class)->callTableAction('deleteToken', $cliente);

        $cliente->refresh();
        $this->assertNotNull(ApiClient::find($cliente->id), 'el cliente no se borra');
        $this->assertFalse((bool) $cliente->is_active);
        $this->assertFalse((bool) $cliente->is_token);
        $this->assertEquals(1, Auditoria::where('evento', 'token_eliminado')->count());

        // los registros de auditoria nunca llevan el token
        foreach (Auditoria::all() as $registro) {
            $this->assertStringNotContainsString('token-de-prueba', json_encode($registro->toArray()));
        }
    }


    // ---------------------------------------------------------- usuarios y personas

    public function test_nadie_puede_borrarse_a_si_mismo_y_rrhh_no_toca_al_administrador(): void
    {
        $admin = $this->usuario('administrador');
        $rrhh = $this->usuario('rrhh');
        $politica = new UserPolicy();

        $this->assertFalse($politica->delete($admin, $admin));
        $this->assertFalse($politica->forceDelete($rrhh, $rrhh));

        $this->assertFalse($politica->update($rrhh, $admin));
        $this->assertFalse($politica->delete($rrhh, $admin));
        $this->assertFalse($politica->view($rrhh, $admin));

        $this->assertTrue($politica->delete($admin, $rrhh));
    }


    private function terminarCreacion(CreatePersona $pagina, Persona $persona): void
    {
        $pagina->record = $persona;

        $metodo = new \ReflectionMethod($pagina, 'afterCreate');
        $metodo->setAccessible(true);
        $metodo->invoke($pagina);
    }


    public function test_el_admin_no_se_queda_con_las_personas_que_carga_para_otros(): void
    {
        $admin = $this->usuario('administrador');
        $this->actingAs($admin);

        $this->terminarCreacion(new CreatePersona(), $this->persona(1));

        $this->assertNull($admin->fresh()->persona_id);
    }


    public function test_desde_mis_datos_personales_si_se_vincula_la_propia_persona(): void
    {
        $admin = $this->usuario('administrador');
        $this->actingAs($admin);

        $pagina = new CreatePersona();
        $pagina->propia = true;

        $mia = $this->persona(2);
        $this->terminarCreacion($pagina, $mia);

        $this->assertEquals($mia->id, $admin->fresh()->persona_id);

        // y una segunda vez ya no la pisa
        $pagina2 = new CreatePersona();
        $pagina2->propia = true;
        $this->terminarCreacion($pagina2, $this->persona(3));

        $this->assertEquals($mia->id, $admin->fresh()->persona_id);
    }


    public function test_el_enlace_de_mis_datos_manda_la_marca_de_persona_propia(): void
    {
        $this->actingAs($this->usuario('rrhh'));

        $this->assertStringContainsString('propia=1', MisDatosPersonales::getNavigationUrl());
    }


    // ---------------------------------------------------------- archivos

    public function test_el_nombre_de_archivo_coincide_con_la_clase_para_linux(): void
    {
        $this->assertFileExists(app_path('Filament/Pages/MisDatosPersonales.php'));
        $this->assertFileDoesNotExist(app_path('Filament/Pages/misdatospersonales.php'));
    }


    public function test_no_quedan_archivos_muertos_ni_marcas_de_merge(): void
    {
        foreach (['Imports/PersonasImport.php', 'Imports/PersonaImport.php', 'Filament/Forms/Components/DocumentoUpload.php', 'Http/Controllers/DocumentoDownloadController.php', 'Filament/Actions/LegajoFromPortAction.php'] as $muerto) {
            $this->assertFileDoesNotExist(app_path($muerto));
        }

        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)) as $archivo) {
            if ($archivo->getExtension() === 'php') {
                $this->assertDoesNotMatchRegularExpression('/^(<<<<<<<|>>>>>>>)/m', file_get_contents($archivo->getPathname()), $archivo->getPathname());
            }
        }

        // y ninguna clave fija en el codigo
        $this->assertStringNotContainsString('Tcse2026', shell_exec('grep -rl "Tcse2026" ' . escapeshellarg(app_path()) . ' 2>/dev/null') ?? '');
    }
}

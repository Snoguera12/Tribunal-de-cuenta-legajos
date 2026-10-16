<?php

namespace Tests\Feature;

use App\Filament\Resources\Auditorias\AuditoriaResource;
use App\Filament\Resources\Auditorias\Pages\ListAuditorias;
use App\Models\Auditoria;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;


class AuditoriaTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function crearPersona(array $extra = []): Persona
    {
        return Persona::create(array_merge([
            'nombre' => 'Ana',
            'apellido' => 'Lopez',
            'dni' => 30111222,
            'cuil' => 27301112228,
            'fecha_de_nacimiento' => '1990-01-01',
        ], $extra));
    }


    public function test_crear_un_registro_queda_anotado(): void
    {
        $persona = $this->crearPersona();

        $registro = Auditoria::where('evento', 'creado')->first();

        $this->assertNotNull($registro);
        $this->assertEquals('Persona', $registro->modelo);
        $this->assertEquals($persona->id, $registro->modelo_id);
        $this->assertEquals('Ana', $registro->valores_nuevos['nombre']);
    }


    public function test_modificar_guarda_antes_y_despues(): void
    {
        $persona = $this->crearPersona();

        $persona->update(['telefono' => '3854000000']);

        $registro = Auditoria::where('evento', 'modificado')->first();

        $this->assertNotNull($registro);
        $this->assertNull($registro->valores_anteriores['telefono']);
        $this->assertEquals('3854000000', $registro->valores_nuevos['telefono']);
        $this->assertArrayNotHasKey('nombre', $registro->valores_nuevos);
    }


    public function test_si_no_cambia_nada_no_anota(): void
    {
        $persona = $this->crearPersona();

        $persona->save();

        $this->assertEquals(0, Auditoria::where('evento', 'modificado')->count());
    }


    public function test_la_contrasena_nunca_se_guarda(): void
    {
        $usuario = User::create([
            'name' => 'Prueba',
            'email' => 'prueba@test.com',
            'password' => 'secreto123',
            'rol' => 'empleado',
        ]);

        $usuario->update(['password' => 'otraclave456']);

        foreach (Auditoria::all() as $registro) {
            $this->assertStringNotContainsString('secreto123', json_encode($registro->toArray()));
            $this->assertStringNotContainsString('otraclave456', json_encode($registro->toArray()));
            $this->assertStringNotContainsString('$2y$', json_encode($registro->toArray()));
        }

        $cambio = Auditoria::where('evento', 'modificado')->first();
        $this->assertEquals('[oculto]', $cambio->valores_nuevos['password']);
    }


    public function test_eliminar_queda_anotado(): void
    {
        $persona = $this->crearPersona();
        $persona->delete();

        $this->assertEquals(1, Auditoria::where('evento', 'eliminado')->count());
    }


    public function test_un_registro_de_auditoria_no_se_puede_editar_ni_borrar(): void
    {
        $this->crearPersona();
        $registro = Auditoria::first();

        $registro->descripcion = 'cambiado';
        $this->assertFalse($registro->save());

        $this->assertFalse($registro->delete());
        $this->assertEquals(1, Auditoria::count());
    }


    public function test_login_fallido_guarda_el_correo_pero_no_la_clave(): void
    {
        event(new Failed('web', null, ['email' => 'x@test.com', 'password' => 'claveSecreta']));

        $registro = Auditoria::where('evento', 'login_fallido')->first();

        $this->assertEquals('x@test.com', $registro->extra['email']);
        $this->assertStringNotContainsString('claveSecreta', json_encode($registro->toArray()));
    }


    public function test_login_guarda_quien_inicio_sesion(): void
    {
        $usuario = User::create([
            'name' => 'Admin', 'email' => 'a@test.com', 'password' => 'x12345678', 'rol' => 'administrador',
        ]);

        event(new Login('web', $usuario, false));

        $registro = Auditoria::where('evento', 'login')->first();

        $this->assertEquals($usuario->id, $registro->actor_id);
        $this->assertEquals('administrador', $registro->actor_rol);
    }


    public function test_si_falla_el_guardado_no_rompe_la_app(): void
    {
        \Illuminate\Support\Facades\Schema::connection('historico')->drop('auditorias');

        // no tiene que tirar excepcion
        $this->assertNull(Auditoria::registrar('login', 'prueba'));
    }


    public function test_solo_el_admin_ve_la_auditoria(): void
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'a@test.com', 'password' => 'x12345678', 'rol' => 'administrador']);
        $rrhh = User::create(['name' => 'RRHH', 'email' => 'r@test.com', 'password' => 'x12345678', 'rol' => 'rrhh']);

        Filament::setCurrentPanel('legajos');
        Auditoria::registrar('login', 'demo');

        $this->actingAs($rrhh);
        $this->assertFalse(AuditoriaResource::canViewAny());

        $this->actingAs($admin);
        $this->assertTrue(AuditoriaResource::canViewAny());

        Livewire::test(ListAuditorias::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords(Auditoria::all());
    }
}

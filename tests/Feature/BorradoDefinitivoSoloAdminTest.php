<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\Documento;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;


class BorradoDefinitivoSoloAdminTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function usuario(string $rol): User
    {
        return User::create(['name' => $rol, 'email' => "{$rol}@test.com", 'password' => 'Clave12345', 'rol' => $rol, 'debe_cambiar_clave' => false]);
    }


    public function test_solo_el_administrador_puede_borrar_para_siempre(): void
    {
        $registros = [
            new Persona(), new Legajo(), new Documento(), new Area(), new Cargo(),
            tap(new User(['rol' => 'empleado']), fn ($u) => $u->id = 999),
        ];

        foreach (['rrhh', 'funcionario', 'empleado'] as $rol) {
            $quien = $this->usuario($rol);

            foreach ($registros as $registro) {
                $this->assertFalse(Gate::forUser($quien)->allows('forceDelete', $registro), "{$rol} no debe borrar para siempre " . class_basename($registro));
            }
        }

        $admin = $this->usuario('administrador');

        foreach ($registros as $registro) {
            $this->assertTrue(Gate::forUser($admin)->allows('forceDelete', $registro), 'el administrador si puede: ' . class_basename($registro));
        }
    }


    public function test_rrhh_sigue_pudiendo_borrar_a_la_papelera_y_restaurar(): void
    {
        $rrhh = $this->usuario('rrhh');

        foreach ([new Persona(), new Legajo(), new Documento()] as $registro) {
            $this->assertTrue(Gate::forUser($rrhh)->allows('delete', $registro), class_basename($registro) . ' delete');
            $this->assertTrue(Gate::forUser($rrhh)->allows('restore', $registro), class_basename($registro) . ' restore');
        }
    }
}

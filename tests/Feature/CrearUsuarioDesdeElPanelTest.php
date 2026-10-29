<?php

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Models\User;
use App\Notifications\UsuarioCreado;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;


class CrearUsuarioDesdeElPanelTest extends TestCase
{
    use RefreshDatabase;

    protected array $connectionsToTransact = [null, 'historico'];


    private function entrarComo(string $rol): void
    {
        Filament::setCurrentPanel('legajos');
        $this->actingAs(User::create(['name' => $rol, 'email' => "{$rol}@test.com", 'password' => 'Clave12345', 'rol' => $rol, 'debe_cambiar_clave' => false]));
    }


    public function test_el_admin_crea_un_usuario_con_clave_provisoria_y_le_llega_el_mail(): void
    {
        $this->entrarComo('administrador');
        Notification::fake();

        Livewire::test(CreateUser::class)
            ->fillForm(['name' => 'Nuevo', 'email' => 'nuevo@test.com', 'password' => 'Provisoria123', 'rol' => 'empleado'])
            ->call('create')
            ->assertHasNoFormErrors();

        $nuevo = User::where('email', 'nuevo@test.com')->first();

        $this->assertNotNull($nuevo);
        $this->assertTrue($nuevo->debe_cambiar_clave);
        Notification::assertSentTo($nuevo, UsuarioCreado::class);
    }


    public function test_el_formulario_rechaza_claves_debiles(): void
    {
        $this->entrarComo('rrhh');

        foreach (['abc', '12345678', 'sinmayusculas123', 'SINMINUSCULAS123', 'SinNumerosAqui'] as $debil) {
            Livewire::test(CreateUser::class)
                ->fillForm(['name' => 'Nuevo', 'email' => 'nuevo@test.com', 'password' => $debil, 'rol' => 'empleado'])
                ->call('create')
                ->assertHasFormErrors(['password']);
        }

        $this->assertNull(User::where('email', 'nuevo@test.com')->first());
    }
}

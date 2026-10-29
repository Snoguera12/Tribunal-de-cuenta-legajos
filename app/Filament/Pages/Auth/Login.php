<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use App\Models\Auditoria;
use App\Models\User;
use App\Services\ControlIntentosLogin;
use App\Services\SesionUnica;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

// el login va por email (no hay columna 'usuario' en users)
class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    public function authenticate(): ?LoginResponse
    {
        $email = ControlIntentosLogin::normalizar($this->data['email'] ?? '');

        // en espera (20 s) o bloqueado: ni se mira la clave
        if ($email !== '' && ($bloqueo = ControlIntentosLogin::bloqueoActual($email))) {
            throw ValidationException::withMessages(['data.email' => $bloqueo]);
        }

        try {
            $respuesta = parent::authenticate();
        } catch (ValidationException $e) {
            // si fue clave mala, el mensaje dice cuantos intentos quedan
            if ($mensaje = request()->attributes->get('login_mensaje')) {
                throw ValidationException::withMessages(['data.email' => $mensaje]);
            }

            throw $e;
        }

        $usuario = Filament::auth()->user();

        if ($usuario instanceof User && SesionUnica::activa()) {
            $this->controlarSesionUnica($usuario);
        }

        return $respuesta;
    }


    // un usuario no puede estar dos veces a la vez
    private function controlarSesionUnica(User $usuario): void
    {
        $idSesion = session()->getId();
        $otra = SesionUnica::hayOtraActiva($usuario, SesionUnica::huella($idSesion));

        if ($otra && SesionUnica::modo() === 'bloquear') {

            Auditoria::registrar(
                evento: 'sesion_duplicada_bloqueada',
                descripcion: "{$usuario->name} intentó ingresar teniendo otra sesión abierta",
                actor: $usuario,
                extra: ['ip_sesion_abierta' => $otra->sesion_activa_ip],
            );

            Filament::auth()->logout();
            session()->invalidate();
            session()->regenerateToken();

            throw ValidationException::withMessages(['data.email' => 'Este usuario ya tiene una sesión abierta en otro equipo o navegador. '
                . 'Cerrala, o volvé a intentar cuando pasen ' . config('sistema.sesion_unica.inactividad_minutos') . ' minutos sin actividad.']);
        }

        SesionUnica::registrar($usuario, $idSesion, request()->ip());
    }


    public function form(Schema $schema): Schema
    {
        return $schema->components([
            $this->getEmailFormComponent(),
            $this->getPasswordFormComponent(),
            $this->getRememberFormComponent(),
        ]);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label('Correo electrónico')
            ->email()
            ->prefixIcon('heroicon-m-user')
            ->required()
            ->autofocus()
            ->autocomplete('username')
            ->extraInputAttributes(['tabindex' => 1]);
    }

    protected function getPasswordFormComponent(): Component
    {
        return TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->prefixIcon('heroicon-m-lock-closed')
            ->revealable(filament()->arePasswordsRevealable())
            ->required()
            ->autocomplete('current-password')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    // clase propia para no pisar .fi-btn con !important
    protected function getAuthenticateFormAction(): Action
    {
        return Action::make('authenticate')
            ->label('Ingresar')
            ->submit('authenticate')
            ->extraAttributes([
                'class' => 'tcse-btn-primary',
                'tabindex' => 3,
            ]);
    }

    // los titulos los dibuja la vista
    public function getHeading(): ?string
    {
        return null;
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}

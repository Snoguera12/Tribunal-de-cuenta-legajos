<?php

declare(strict_types=1);

namespace App\Filament\Pages\Auth;

use App\Models\Auditoria;
use App\Models\User;
use App\Services\ControlIntentosLogin;
use App\Services\SesionUnica;
use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login as BaseLogin;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Validation\ValidationException;

/**
 * Verificado contra Filament v4.11.4 y contra el modelo App\Models\User,
 * cuyo $fillable es ['name', 'email', 'password', 'rol', 'persona_id'].
 *
 * El login va por 'email'. NO hay columna 'usuario' en la tabla: si se
 * cambia el campo, el login deja de funcionar (ver nota al pie).
 */
class Login extends BaseLogin
{
    protected string $view = 'filament.pages.auth.login';

    // Control de intentos de clave (ver config/sistema.php, 'login')
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
            // si fue clave mala, el mensaje dice cuántos intentos quedan
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
            ->autocomplete('off')
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
            ->autocomplete('new-password')
            ->extraInputAttributes(['tabindex' => 2]);
    }

    /**
     * El gancho de clase evita pisar .fi-btn con !important.
     */
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

    /**
     * Los encabezados los dibuja la vista, no la página. Devolver null
     * evita que se rendericen dos veces.
     */
    public function getHeading(): ?string
    {
        return null;
    }

    public function getSubheading(): ?string
    {
        return null;
    }
}

/*
 * ══════════════════════════════════════════════════════════════════════
 * SI EN ALGÚN MOMENTO SE QUIERE LOGIN POR NOMBRE DE USUARIO
 *
 * No alcanza con cambiar la etiqueta. Hacen falta cuatro cosas:
 *
 *   1. Migración que agregue la columna `usuario` a `users`, única y
 *      no nula, más el backfill de los registros existentes.
 *   2. Agregarla al $fillable del modelo User.
 *   3. Cambiar TextInput::make('email') por make('usuario'), sacar el
 *      ->email() y sobrescribir getCredentialsFromFormData().
 *   4. Sobrescribir throwFailureValidationException(). La clase base
 *      clava el error en 'data.email':
 *
 *          throw ValidationException::withMessages([
 *              'data.email' => __('...login.messages.failed'),
 *          ]);
 *
 *      Sin ese cuarto paso, un login fallido no muestra ningún mensaje:
 *      el error se adjunta a un campo que no existe en el formulario.
 * ══════════════════════════════════════════════════════════════════════
 */
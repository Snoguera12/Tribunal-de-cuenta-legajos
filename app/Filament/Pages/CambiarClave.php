<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Facades\Filament;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;


// el usuario cambia su propia clave (obligatorio en el primer ingreso)
class CambiarClave extends Page implements HasForms
{
    use InteractsWithForms;

    protected static bool $shouldRegisterNavigation = false;
    protected static ?string $slug = 'cambiar-clave';
    protected static ?string $title = 'Cambiar contraseña';

    protected string $view = 'filament.pages.cambiar-clave';

    public ?array $data = [];


    public function mount(): void
    {
        $this->form->fill();
    }


    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                TextInput::make('actual')
                    ->label('Clave actual')
                    ->password()->revealable()
                    ->required(),

                TextInput::make('nueva')
                    ->label('Clave nueva')
                    ->password()->revealable()
                    ->required()
                    ->rule(Password::min(10)->letters()->mixedCase()->numbers())
                    ->helperText('Mínimo 10 caracteres, con mayúsculas, minúsculas y números.'),

                TextInput::make('confirmar')
                    ->label('Repetir clave nueva')
                    ->password()->revealable()
                    ->required()
                    ->same('nueva'),
            ])
            ->statePath('data');
    }


    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')->label('Cambiar clave')->submit('guardar')->formId('form'),
        ];
    }


    public function guardar()
    {
        $datos = $this->form->getState();
        $usuario = auth()->user();

        if (! Hash::check($datos['actual'], $usuario->password)) {
            throw ValidationException::withMessages(['data.actual' => 'La clave actual no es correcta.']);
        }

        if (Hash::check($datos['nueva'], $usuario->password) || mb_strtolower($datos['nueva']) === mb_strtolower($usuario->email)) {
            throw ValidationException::withMessages(['data.nueva' => 'La clave nueva tiene que ser distinta de la actual y del correo.']);
        }

        // el modelo la guarda hasheada y marca que ya es propia
        $usuario->update(['password' => $datos['nueva']]);

        // para que filament no cierre esta misma sesion por el cambio de clave
        if (request()->hasSession()) {
            request()->session()->put('password_hash_' . Filament::getAuthGuard(), $usuario->fresh()->getAuthPassword());
        }

        Notification::make()->title('Clave cambiada')->success()->send();

        return redirect()->to(Filament::getUrl());
    }
}

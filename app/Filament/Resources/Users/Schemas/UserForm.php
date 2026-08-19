<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Persona;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Hash;
use Illuminate\Validation\Rules\Password;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nombre de Usuario')
                ->required(),
                
                TextInput::make('email')->label('Correo Electrónico')
                ->email()
                ->unique(ignoreRecord: true)
                ->default(function () {
                    // Captura el 'persona_id' enviado desde la URL
                    $personaId = request()->query('persona_id');
                    
                    if ($personaId) {
                        // Busca la persona y retorna su correo
                        return Persona::find($personaId)?->email;
                    }
                    
                    return null;
                })
                ->required(),

                TextInput::make('password')
                ->label('Contraseña')
                ->password()
                ->required(fn (string $context): bool => $context === 'create')
                // Si el campo está vacío al guardar, no lo incluye en el Query de actualización
                ->dehydrated(fn (?string $state) => filled($state))

                // Encripta la contraseña automáticamente antes de guardarla (si se modificó)
                ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
                // pedimos minimo de seguridad para que no pongan contraseñas como "1234"
                ->rule(Password::min(8)->mixedCase()->numbers())
                ->helperText(function (string $context) {
                    if ($context === 'edit') {
                        return 'Deje este campo en blanco si no desea cambiar la contraseña. Mínimo 8 caracteres, con mayúsculas, minúsculas y números.';
                    }
                    return 'Mínimo 8 caracteres, con mayúsculas, minúsculas y números.';
                }),

                Select::make('rol')->label('Rol del Usuario')
                ->options([
                    1 => 'Empleado',
                    2 => 'Funcionario',
                    3 => 'RRHH (Recursos Humanos.)',
                    //4 => 'Administrador',
                ]) // 'Empleado', 'Funcionario', 'RRHH', 'Administrador'
                ->required(),

                Select::make("persona_id")->label("Persona")
                ->searchable()
                ->nullable()
                ->default(fn () => request()->query('persona_id'))
                ->disabled(fn () => request()->has('persona_id')) // Opcional: deshabilita el campo si ya viene en la URL
                ->placeholder("Ninguna persona")
                ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id')),

            ]);
    }
}

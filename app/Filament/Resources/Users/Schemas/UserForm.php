<?php

namespace App\Filament\Resources\Users\Schemas;

use App\Models\Persona;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Hash;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            TextInput::make('name')
            ->label('Nombre de Usuario')
            ->required(),

                     TextInput::make('email')
                     ->label('Correo Electrónico')
                     ->email()
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

                     //DateTimePicker::make('email_verified_at')->label('Correo Verificado'),

                     TextInput::make('password')
                     ->label('Contraseña')
                     ->password()
                     ->revealable()
                     ->required(fn (string $context): bool => $context === 'create')
                     ->minLength(8)
                     ->rules(['regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/'])
                     ->same('password_confirmation')
                     ->dehydrated(fn (?string $state) => filled($state))
                     ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
                     ->helperText(function (string $context) {
                         if ($context === 'edit') {
                             return 'Deje este campo en blanco si no desea cambiar la contraseña.';
                         }
                         return null;
                     }),

                     TextInput::make('password_confirmation')
                     ->label('Confirmar Contraseña')
                     ->password()
                     ->revealable()
                     ->dehydrated(false)
                     ->required(fn (string $context): bool => $context === 'create')
                     ->visible(fn (string $context): bool => $context === 'create'),

                     Select::make('rol')
                     ->label('Rol del Usuario')
                     ->options([
                         1 => 'Empleado',
                         2 => 'Funcionario',
                         3 => 'RRHH (Recursos Humanos.)',
                     ])
                     ->rules(['in:1,2,3'])
                     ->required(),

                     Select::make("persona_id")
                     ->label("Persona")
                     ->searchable()
                     ->nullable()
                     ->default(fn () => request()->query('persona_id'))
                     ->disabled(fn () => request()->has('persona_id')) // Opcional: deshabilita el campo si ya viene en la URL
                     ->placeholder("Ninguna persona")
                     ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id')),
        ]);
    }
}
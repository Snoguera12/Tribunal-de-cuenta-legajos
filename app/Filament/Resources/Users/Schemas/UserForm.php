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
                    ->required(),
                DateTimePicker::make('email_verified_at')
                    ->label('Correo Verificado'),
                TextInput::make('password')
                    ->label('Contraseña')
                    
                    ->password()
                    ->required(fn (string $context): bool => $context === 'create')
                    // Si el campo está vacío al guardar, no lo incluye en el Query de actualización
                    ->dehydrated(fn (?string $state) => filled($state))

                    // Encripta la contraseña automáticamente antes de guardarla (si se modificó)
                    ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
                    ->helperText(function (string $context) {
                        if ($context === 'edit') {
                            return 'Deje este campo en blanco si no desea cambiar la contraseña.';
                        }
                        return null; // No muestra nada al crear
                    }),
                Select::make('rol')
                    ->label('Rol del Usuario')
                    ->options([
                        1 => 'Empleado',
                        2 => 'Funcionario',
                        3 => 'RRHH (Recursos Humanos.)',
                        4 => 'Administrador',
                    ]) // 'Empleado', 'Funcionario', 'RRHH', 'Administrador'
                    ->required(),
                Select::make("persona_id")
                        ->label("Persona")
                        ->searchable()
                        ->nullable()
                        ->placeholder("Ninguna persona")
                        ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id')),
            ]);
    }
}

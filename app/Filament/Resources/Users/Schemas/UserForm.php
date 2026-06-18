<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->label('Nombre')
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
                    ->required(),
                Select::make('rol')
                    ->label('Rol del Usuario')
                    ->options([
                        1 => 'Empleado',
                        2 => 'Funcionario',
                        3 => 'RRHH (Recursos Humanos.)',
                        4 => 'Administrador',
                    ]) // 'Empleado', 'Funcionario', 'RRHH', 'Administrador'
                    ->required(),
            ]);
    }
}

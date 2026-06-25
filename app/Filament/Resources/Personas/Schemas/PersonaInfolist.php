<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PersonaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Datos Personales')
                    ->schema([
                        TextEntry::make('nombre')
                            ->label('Nombre'),
                        TextEntry::make('apellido')
                            ->label('Apellido'),
                        TextEntry::make('dni')
                            ->label('DNI')
                            ->numeric(),
                        TextEntry::make('cuil')
                            ->label('CUIL'),
                        TextEntry::make('email')
                            ->label('Correo Electrónico')
                            ->placeholder('-'),
                        TextEntry::make('genero')
                            ->label('Género')
                            ->numeric()
                            ->placeholder('-')
                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                0 => 'Femenino',
                                1 => 'Masculino',
                                2 => 'Otro',
                                default => 'Desconocido',
                            }),
                        TextEntry::make('estado_civil')
                            ->label('Estado Civil')
                            ->numeric()
                            ->placeholder('-')
                            ->formatStateUsing(fn (int $state): string => match ($state) {
                                0 => 'Soltero/a',
                                1 => 'Casado/a',
                                2 => 'Viúdo/a',
                                default => 'Desconocido',
                            }),
                        TextEntry::make('fecha_de_nacimiento')
                            ->date('d/m/Y'),
                        TextEntry::make('domicilio')
                            ->placeholder('-'),
                        TextEntry::make('telefono')
                            ->label('Teléfono')
                            ->placeholder('-'),
                        TextEntry::make('telefono_emergencia')
                            ->label('Teléfono de Emergencia')
                            ->placeholder('-'),
                    ])->columns(3) ,
                Section::make('Familiares')
                    ->schema([

                    ]),
            ]);
    }
}

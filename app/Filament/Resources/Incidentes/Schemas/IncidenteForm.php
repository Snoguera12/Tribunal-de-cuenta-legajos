<?php

namespace App\Filament\Resources\Incidentes\Schemas;

use App\Models\Incidente;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\KeyValueEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;


class IncidenteForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([

            Section::make('Qué pasó')
            ->columns(3)
            ->columnSpanFull()
            ->schema([
                TextEntry::make('created_at')->label('Fecha')->dateTime('d/m/Y H:i:s'),
                TextEntry::make('tipo')->label('Tipo')
                ->formatStateUsing(fn (string $state) => Incidente::tipos()[$state] ?? $state),
                TextEntry::make('severidad')->label('Gravedad')->badge(),
                TextEntry::make('descripcion')->label('Descripción')->columnSpanFull(),
                TextEntry::make('actor_nombre')->label('Usuario')->placeholder('-'),
                TextEntry::make('ip')->label('IP')->placeholder('-'),
                TextEntry::make('mac')->label('MAC')->placeholder('no disponible'),
                TextEntry::make('auditoria_id')->label('Registro de auditoría')->placeholder('-'),
                KeyValueEntry::make('detalle')->label('Detalle')->columnSpanFull(),
            ]),

            Section::make('Seguimiento')
            ->columnSpanFull()
            ->schema([
                Select::make('estado')
                ->label('Estado')
                ->options(Incidente::estados())
                ->required(),

                Textarea::make('notas')
                ->label('Notas del administrador')
                ->rows(4),
            ]),

        ]);
    }
}

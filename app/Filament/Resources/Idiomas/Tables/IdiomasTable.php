<?php

namespace App\Filament\Resources\Idiomas\Tables;

use App\Models\Idioma;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;

class IdiomasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...Idioma::getOutSchema('table'),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()->iconButton()->color('primary')->icon('heroicon-m-pencil-square'),
                Action::make('fusionar')
                    ->label('Fusionar en…')
                    ->icon('heroicon-m-arrows-pointing-in')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalDescription('Los idiomas de las personas pasarán al idioma elegido y este se eliminará del catálogo.')
                    ->schema([
                        Select::make('destino_id')
                            ->label('Fusionar en el idioma')
                            ->options(fn (Idioma $record) => Idioma::whereKeyNot($record->id)->orderBy('nombre')->pluck('nombre', 'id'))
                            ->searchable()
                            ->required(),
                    ])
                    ->action(fn (Idioma $record, array $data) => $record->fusionarEn(Idioma::findOrFail($data['destino_id']))),
            ])
            ->toolbarActions([
                //
            ]);
    }
}
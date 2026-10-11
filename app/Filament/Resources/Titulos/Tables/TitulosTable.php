<?php

namespace App\Filament\Resources\Titulos\Tables;

use App\Models\Titulo;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class TitulosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ...Titulo::getOutSchema('table'),
                
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
                ->modalDescription('Los estudios de este título pasarán al título elegido y este título se eliminará.')
                ->requiresConfirmation()
                ->schema([
                    Select::make('destino_id')
                    ->label('Fusionar en el título')
                    ->options(fn (Titulo $record) => Titulo::whereKeyNot($record->id)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->required(),
                ])
                ->action(fn (Titulo $record, array $data) => $record->fusionarEn(Titulo::findOrFail($data['destino_id']))),

            ])
            ->toolbarActions([
                //
            ]);
    }
}

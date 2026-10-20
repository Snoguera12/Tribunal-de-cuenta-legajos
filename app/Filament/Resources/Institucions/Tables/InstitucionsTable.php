<?php

namespace App\Filament\Resources\Institucions\Tables;

use App\Models\Institucion;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Tables\Table;

class InstitucionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Institucion::getOutSchema('table'))
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
                ->modalDescription('Los estudios y cursos de esta institución pasarán a la institución elegida y esta se eliminará.')
                ->schema([
                    Select::make('destino_id')
                    ->label('Fusionar en la institución')
                    ->options(fn (Institucion $record) => Institucion::whereKeyNot($record->id)->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->required(),
                ])
                ->action(fn (Institucion $record, array $data) => $record->fusionarEn(Institucion::findOrFail($data['destino_id']))),
            ])
            ->toolbarActions([]);
    }
}

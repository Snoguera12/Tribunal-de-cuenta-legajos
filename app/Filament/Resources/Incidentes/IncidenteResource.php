<?php

namespace App\Filament\Resources\Incidentes;

use App\Filament\Resources\Incidentes\Pages\EditIncidente;
use App\Filament\Resources\Incidentes\Pages\ListIncidentes;
use App\Filament\Resources\Incidentes\Schemas\IncidenteForm;
use App\Filament\Resources\Incidentes\Tables\IncidentesTable;
use App\Models\Incidente;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;


class IncidenteResource extends Resource
{
    protected static ?string $model = Incidente::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-shield-exclamation';
    protected static string|\UnitEnum|null $navigationGroup = 'Seguridad';

    protected static ?string $modelLabel = 'incidente';
    protected static ?string $pluralModelLabel = 'Incidentes';

    protected static ?int $navigationSort = 11;


    public static function getNavigationBadge(): ?string
    {
        if (! auth()->user()?->isAdmin()) {
            return null;
        }

        $abiertos = Incidente::where('estado', 'abierto')->count();

        return $abiertos > 0 ? (string) $abiertos : null;
    }


    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }


    public static function form(Schema $schema): Schema
    {
        return IncidenteForm::configure($schema);
    }


    public static function table(Table $table): Table
    {
        return IncidentesTable::configure($table);
    }


    public static function canCreate(): bool
    {
        return false;
    }


    public static function getPages(): array
    {
        return [
            'index' => ListIncidentes::route('/'),
            'edit' => EditIncidente::route('/{record}/seguimiento'),
        ];
    }
}

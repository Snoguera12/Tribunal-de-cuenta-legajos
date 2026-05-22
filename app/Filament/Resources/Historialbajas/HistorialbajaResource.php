<?php

namespace App\Filament\Resources\Historialbajas;

use App\Filament\Resources\Historialbajas\Pages\CreateHistorialbaja;
use App\Filament\Resources\Historialbajas\Pages\EditHistorialbaja;
use App\Filament\Resources\Historialbajas\Pages\ListHistorialbajas;
use App\Filament\Resources\Historialbajas\Schemas\HistorialbajaForm;
use App\Filament\Resources\Historialbajas\Tables\HistorialbajasTable;
use App\Models\Historialbaja;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class HistorialbajaResource extends Resource
{
    protected static ?string $model = Historialbaja::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Papeles";
    public static function getNavigationBadge(): ?string{
        return Historialbaja::count();
    }

    public static function getNavigationBadgeColor(): string|array|null{
        return "succes";
    }
    public static function form(Schema $schema): Schema
    {
        return HistorialbajaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return HistorialbajasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHistorialbajas::route('/'),
            'create' => CreateHistorialbaja::route('/create'),
            'edit' => EditHistorialbaja::route('/{record}/edit'),
        ];
    }
}

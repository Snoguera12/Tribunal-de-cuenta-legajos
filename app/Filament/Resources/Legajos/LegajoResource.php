<?php

namespace App\Filament\Resources\Legajos;

use App\Filament\Resources\Legajos\Pages\CreateLegajo;
use App\Filament\Resources\Legajos\Pages\EditLegajo;
use App\Filament\Resources\Legajos\Pages\ListLegajos;
use App\Filament\Resources\Legajos\Schemas\LegajoForm;
use App\Filament\Resources\Legajos\Tables\LegajosTable;
use App\Models\Legajo;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Collection;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Notifications\Notification;

class LegajoResource extends Resource
{
    protected static ?string $model = Legajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Papeles";

    public static function getNavigationBadge(): ?string{
        return Legajo::count();
    }

    public static function getNavigationBadgeColor(): string|array|null{
        return "succes";
    }
    public static function form(Schema $schema): Schema
    {
        return LegajoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return LegajosTable::configure($table);
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
            'index' => ListLegajos::route('/'),
            'create' => CreateLegajo::route('/create'),
            'edit' => EditLegajo::route('/{record}/edit'),
        ];
    }
}

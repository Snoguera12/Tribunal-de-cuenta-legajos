<?php

namespace App\Filament\Resources\Legajos;

use App\Filament\Resources\Legajos\Pages\CreateLegajo;
use App\Filament\Resources\Legajos\Pages\EditLegajo;
use App\Filament\Resources\Legajos\Pages\ListLegajos;
use App\Filament\Resources\Legajos\Schemas\LegajoForm;
use App\Filament\Resources\Legajos\Tables\LegajosTable;
use App\Models\Historialbaja;
use App\Models\Legajo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class LegajoResource extends Resource
{
    protected static ?string $model = Legajo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Papeles";
    
    protected static ?int $navigationSort = 1;
    
    /*public static function getNavigationBadge(): ?string{
        return Legajo::count();
    }*/

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
            'create' => CreateLegajo::route('/crear'),
            'edit' => EditLegajo::route('/{record}/editar'),
        ];
    }
}

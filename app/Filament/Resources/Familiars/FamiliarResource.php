<?php

namespace App\Filament\Resources\Familiars;

use App\Filament\Resources\Familiars\Pages\CreateFamiliar;
use App\Filament\Resources\Familiars\Pages\EditFamiliar;
use App\Filament\Resources\Familiars\Pages\ListFamiliars;
use App\Filament\Resources\Familiars\Schemas\FamiliarForm;
use App\Filament\Resources\Familiars\Tables\FamiliarsTable;
use App\Models\Familiar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class FamiliarResource extends Resource
{
    protected static ?string $model = Familiar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $modelLabel = "Familiares";

    public static function form(Schema $schema): Schema
    {
        return FamiliarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FamiliarsTable::configure($table);
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
            'index' => ListFamiliars::route('/'),
            'create' => CreateFamiliar::route('/create'),
            'edit' => EditFamiliar::route('/{record}/edit'),
        ];
    }
}

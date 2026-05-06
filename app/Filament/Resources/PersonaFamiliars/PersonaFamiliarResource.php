<?php

namespace App\Filament\Resources\PersonaFamiliars;

use App\Filament\Resources\PersonaFamiliars\Pages\CreatePersonaFamiliar;
use App\Filament\Resources\PersonaFamiliars\Pages\EditPersonaFamiliar;
use App\Filament\Resources\PersonaFamiliars\Pages\ListPersonaFamiliars;
use App\Filament\Resources\PersonaFamiliars\Schemas\PersonaFamiliarForm;
use App\Filament\Resources\PersonaFamiliars\Tables\PersonaFamiliarsTable;
use App\Models\PersonaFamiliar;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PersonaFamiliarResource extends Resource
{
    protected static ?string $model = PersonaFamiliar::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return PersonaFamiliarForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonaFamiliarsTable::configure($table);
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
            'index' => ListPersonaFamiliars::route('/'),
            'create' => CreatePersonaFamiliar::route('/create'),
            'edit' => EditPersonaFamiliar::route('/{record}/edit'),
        ];
    }
}

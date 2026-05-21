<?php

namespace App\Filament\Resources\Idiomas;

use App\Filament\Resources\Idiomas\Pages\CreateIdioma;
use App\Filament\Resources\Idiomas\Pages\EditIdioma;
use App\Filament\Resources\Idiomas\Pages\ListIdiomas;
use App\Filament\Resources\Idiomas\Schemas\IdiomaForm;
use App\Filament\Resources\Idiomas\Tables\IdiomasTable;
use App\Models\Idioma;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IdiomaResource extends Resource
{
    protected static ?string $model = Idioma::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    public static function getNavigationBadge(): ?string{
        return Idioma::count();
    }

    public static function getNavigationBadgeColor(): string|array|null{
        return "succes";
    }
    public static function form(Schema $schema): Schema
    {
        return IdiomaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdiomasTable::configure($table);
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
            'index' => ListIdiomas::route('/'),
            'create' => CreateIdioma::route('/create'),
            'edit' => EditIdioma::route('/{record}/edit'),
        ];
    }
}

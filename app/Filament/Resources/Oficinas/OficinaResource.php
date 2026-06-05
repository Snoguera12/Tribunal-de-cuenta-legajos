<?php

namespace App\Filament\Resources\Oficinas;

use App\Filament\Resources\Oficinas\Pages\CreateOficina;
use App\Filament\Resources\Oficinas\Pages\EditOficina;
use App\Filament\Resources\Oficinas\Pages\ListOficinas;
use App\Filament\Resources\Oficinas\Schemas\OficinaForm;
use App\Filament\Resources\Oficinas\Tables\OficinasTable;
use App\Models\Oficina;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OficinaResource extends Resource
{
    protected static ?string $model = Oficina::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Departamentos";
    protected static ?string $modelLabel = "Oficinas";
    protected static ?int $navigationSort = 20;
    public static function form(Schema $schema): Schema
    {
        return OficinaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OficinasTable::configure($table);
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
            'index' => ListOficinas::route('/'),
            'create' => CreateOficina::route('/create'),
            'edit' => EditOficina::route('/{record}/edit'),
        ];
    }
}

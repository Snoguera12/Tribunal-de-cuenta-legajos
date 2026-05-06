<?php

namespace App\Filament\Resources\AntecedenteLaborals;

use App\Filament\Resources\AntecedenteLaborals\Pages\CreateAntecedenteLaboral;
use App\Filament\Resources\AntecedenteLaborals\Pages\EditAntecedenteLaboral;
use App\Filament\Resources\AntecedenteLaborals\Pages\ListAntecedenteLaborals;
use App\Filament\Resources\AntecedenteLaborals\Schemas\AntecedenteLaboralForm;
use App\Filament\Resources\AntecedenteLaborals\Tables\AntecedenteLaboralsTable;
use App\Models\AntecedenteLaboral;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AntecedenteLaboralResource extends Resource
{
    protected static ?string $model = AntecedenteLaboral::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return AntecedenteLaboralForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AntecedenteLaboralsTable::configure($table);
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
            'index' => ListAntecedenteLaborals::route('/'),
            'create' => CreateAntecedenteLaboral::route('/create'),
            'edit' => EditAntecedenteLaboral::route('/{record}/edit'),
        ];
    }
}

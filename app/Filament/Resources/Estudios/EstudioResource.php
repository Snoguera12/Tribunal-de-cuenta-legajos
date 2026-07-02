<?php

namespace App\Filament\Resources\Estudios;

use App\Filament\Resources\Estudios\Pages\CreateEstudio;
use App\Filament\Resources\Estudios\Pages\EditEstudio;
use App\Filament\Resources\Estudios\Pages\ListEstudios;
use App\Filament\Resources\Estudios\Schemas\EstudioForm;
use App\Filament\Resources\Estudios\Tables\EstudiosTable;
use App\Models\Estudio;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class EstudioResource extends Resource
{
    protected static ?string $model = Estudio::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    protected static string|\UnitEnum|null $navigationGroup = "Agentes";
    protected static ?string $modelLabel = 'Estudio';
    protected static ?string $pluralModelLabel = 'Estudios';
    protected static ?int $navigationSort = 10;
    public static function form(Schema $schema): Schema
    {
        return EstudioForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return EstudiosTable::configure($table);
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
            'index' => ListEstudios::route('/'),
            'create' => CreateEstudio::route('/create'),
            'edit' => EditEstudio::route('/{record}/edit'),
        ];
    }
}

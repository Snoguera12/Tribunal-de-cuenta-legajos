<?php

namespace App\Filament\Resources\Institucions;

use App\Filament\Resources\Institucions\Pages\CreateInstitucion;
use App\Filament\Resources\Institucions\Pages\EditInstitucion;
use App\Filament\Resources\Institucions\Pages\ListInstitucions;
use App\Filament\Resources\Institucions\Pages\PendientesInstitucion;
use App\Filament\Resources\Institucions\Schemas\InstitucionForm;
use App\Filament\Resources\Institucions\Tables\InstitucionsTable;
use App\Models\Institucion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class InstitucionResource extends Resource
{
    protected static ?string $navigationLabel = 'Institución';
    protected static ?string $modelLabel = 'Institución';
    protected static ?string $pluralModelLabel = 'Institución';
    protected static string|\UnitEnum|null $navigationGroup = "Departamento";
    protected static ?string $model = Institucion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return InstitucionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstitucionsTable::configure($table);
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
            'index' => ListInstitucions::route('/'),
            'pendientes' => PendientesInstitucion::route('/pendientes'),
            //'create' => CreateInstitucion::route('/crear'),
            //'edit' => EditInstitucion::route('/{record}/editar'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Institucions;

use App\Filament\Resources\Institucions\Pages\ListInstitucions;
use App\Filament\Resources\Institucions\Pages\PendientesInstitucion;
use App\Filament\Resources\Institucions\Schemas\InstitucionForm;
use App\Filament\Resources\Institucions\Tables\InstitucionsTable;
use App\Models\Curso;
use App\Models\Estudio;
use App\Models\Institucion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class InstitucionResource extends Resource
{
    protected static ?string $navigationLabel = 'Instituciones';
    protected static ?string $modelLabel = 'Institución';
    protected static ?string $pluralModelLabel = 'Instituciones';
    protected static string|\UnitEnum|null $navigationGroup = "Departamento";
    protected static ?string $model = Institucion::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-academic-cap';
    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return InstitucionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return InstitucionsTable::configure($table);
    }
    public static function getNavigationBadge(): ?string
    {
        // Retorna el conteo total de la base de datos
        return Estudio::pendientes()->count() + Curso::pendientes()->count();
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

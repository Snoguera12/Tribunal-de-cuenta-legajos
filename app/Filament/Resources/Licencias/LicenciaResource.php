<?php

namespace App\Filament\Resources\Licencias;

use App\Filament\Resources\Licencias\Pages\ListLicencias;
use App\Filament\Resources\Licencias\Tables\LicenciasTable;
use App\Models\Licencia;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LicenciaResource extends Resource
{
    protected static ?string $model = Licencia::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';
    protected static string|\UnitEnum|null $navigationGroup = 'Papeles';
    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'licencia aprobada';
    protected static ?string $pluralModelLabel = 'licencias aprobadas';

    // Las licencias entran solo por la API.
    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return LicenciasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['legajo.persona']);
        $user = auth()->user();

        if ($user->isEmpleado()) {
            return $query->whereHas('legajo', fn (Builder $q) => $q->where('persona_id', $user->persona_id));
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListLicencias::route('/'),
        ];
    }
}

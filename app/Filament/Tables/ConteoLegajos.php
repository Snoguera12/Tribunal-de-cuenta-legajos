<?php

namespace App\Filament\Tables;

use App\Enums\EstadoLegajoEnum;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Model;

/**
 * Columna "Empleados Asociados" + toggle "solo activos", para Áreas, Cargos y Categorías.
 * El modelo tiene que tener la relación legajos().
 */
class ConteoLegajos
{
    private const FILTRO = 'solo_activos';

    public static function filtro(): Filter
    {
        return Filter::make(self::FILTRO)
            ->label('Contar solo legajos activos (Alta)')
            ->toggle();
    }

    public static function columna(): TextColumn
    {
        return TextColumn::make('legajos_count')
            ->label('Empleados Asociados')
            ->state(function (Model $record, $livewire): int {
                $soloActivos = (bool) ($livewire->getTableFilterState(self::FILTRO)['isActive'] ?? false);

                return $record->legajos()
                    ->when($soloActivos, fn ($consulta) => $consulta->where('estado', EstadoLegajoEnum::Alta->value))
                    ->count();
            });
    }
}
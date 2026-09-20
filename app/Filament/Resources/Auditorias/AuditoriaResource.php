<?php

namespace App\Filament\Resources\Auditorias;

use App\Filament\Resources\Auditorias\Pages\ListAuditorias;
use App\Filament\Resources\Auditorias\Tables\AuditoriasTable;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Spatie\Activitylog\Models\Activity;

class AuditoriaResource extends Resource
{
    protected static ?string $model = Activity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ClipboardDocumentCheck;
    protected static string|\UnitEnum|null $navigationGroup = "Departamento";
    protected static ?string $navigationLabel = "Auditoría";
    protected static ?string $modelLabel = "Registro de Auditoría";
    protected static ?string $pluralModelLabel = "Auditoría";
    protected static ?int $navigationSort = 10;

    public static function table(Table $table): Table
    {
        return AuditoriasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
        ];
    }

    // esto es solo lectura, no se puede crear nada a mano
    public static function canCreate(): bool
    {
        return false;
    }
}

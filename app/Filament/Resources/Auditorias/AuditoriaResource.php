<?php

namespace App\Filament\Resources\Auditorias;

use App\Filament\Resources\Auditorias\Pages\ListAuditorias;
use App\Filament\Resources\Auditorias\Pages\ViewAuditoria;
use App\Filament\Resources\Auditorias\Schemas\AuditoriaInfolist;
use App\Filament\Resources\Auditorias\Tables\AuditoriasTable;
use App\Models\Auditoria;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;


class AuditoriaResource extends Resource
{
    protected static ?string $model = Auditoria::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static string|\UnitEnum|null $navigationGroup = 'Seguridad';

    protected static ?string $modelLabel = 'registro de auditoría';
    protected static ?string $pluralModelLabel = 'Auditoría';

    protected static ?int $navigationSort = 10;


    public static function infolist(Schema $schema): Schema
    {
        return AuditoriaInfolist::configure($schema);
    }


    public static function table(Table $table): Table
    {
        return AuditoriasTable::configure($table);
    }


    // la auditoria solo se lee, no se crea a mano
    public static function canCreate(): bool
    {
        return false;
    }


    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
            'view' => ViewAuditoria::route('/{record}'),
        ];
    }
}

<?php

namespace App\Filament\Resources\Auditorias;

use App\Filament\Resources\Auditorias\Pages\ListAuditorias;
<<<<<<< HEAD
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

=======
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


>>>>>>> version2-20261014_210855
    public static function table(Table $table): Table
    {
        return AuditoriasTable::configure($table);
    }

<<<<<<< HEAD
    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
        ];
    }

    // esto es solo lectura, no se puede crear nada a mano
=======

    // la auditoria solo se lee, no se crea a mano
>>>>>>> version2-20261014_210855
    public static function canCreate(): bool
    {
        return false;
    }
<<<<<<< HEAD
=======


    public static function getPages(): array
    {
        return [
            'index' => ListAuditorias::route('/'),
            'view' => ViewAuditoria::route('/{record}'),
        ];
    }
>>>>>>> version2-20261014_210855
}

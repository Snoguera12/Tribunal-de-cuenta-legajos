<?php

namespace App\Filament\Resources\Documentos;

use App\Filament\Resources\Documentos\Pages\CreateDocumento;
use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Filament\Resources\Documentos\Pages\SubirDocumentos;
use App\Filament\Resources\Documentos\Schemas\DocumentoForm;
use App\Filament\Resources\Documentos\Tables\DocumentosTable;
use App\Models\Documento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-document-text';
    protected static string|\UnitEnum|null $navigationGroup = "Papeles";
    protected static ?int $navigationSort = 5;

    public static function form(Schema $schema): Schema
    {
        return DocumentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentosTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isEmpleado()) {
            return $query->whereHas('legajo', fn ($q) => $q->where('persona_id', $user->persona_id));
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::whereNull('descripcion')
                ->orWhereNull('tipodoc')
                ->orWhereNull('legajo_id')->count();
    }
    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
            'create' => CreateDocumento::route('/crear'),
            //'edit' => EditDocumento::route('/{record}/editar'),
            'subir' => SubirDocumentos::route('/subir'),
        ];
    }
}
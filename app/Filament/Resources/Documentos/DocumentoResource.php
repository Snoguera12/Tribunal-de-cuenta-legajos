<?php

namespace App\Filament\Resources\Documentos;

use App\Filament\Resources\Documentos\Pages\CreateDocumento;
use App\Filament\Resources\Documentos\Pages\EditDocumento;
use App\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Filament\Resources\Documentos\Schemas\DocumentoForm;
use App\Filament\Resources\Documentos\Tables\DocumentosTable;
use App\Models\Documento;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentoResource extends Resource
{
    protected static ?string $model = Documento::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedPaperClip;
    protected static string|\UnitEnum|null $navigationGroup = "Papeles";

    protected static ?int $navigationSort = 10;

    public static function form(Schema $schema): Schema
    {
        return DocumentoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocumentosTable::configure($table);
    }

    // <-- El método va aquí afuera, respetando el nivel de la clase
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

    public static function getPages(): array
    {
        return [
            'index' => ListDocumentos::route('/'),
            'create' => CreateDocumento::route('/create'),
            'edit' => EditDocumento::route('/{record}/edit'),
        ];
    }
}
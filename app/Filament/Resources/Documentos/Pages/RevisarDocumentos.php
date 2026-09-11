<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\DocumentoResource;
use App\Models\Documento;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Illuminate\Support\Facades\URL;

class RevisarDocumentos extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = DocumentoResource::class;
    protected string $view = 'filament.pages.revisar-documentos';
    public static function canAccess(array $parameters = []): bool{
        if(!auth()->user()->isAdmin_RRHH()){
            abort(403, 'No tienes acceso a esta función');
        }
        return true;
    }
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Documento::query()
                ->whereNull('legajo_id') // agregá acá otras condiciones si hay más campos incompletos
            )
            ->columns([
                TextColumn::make('ruta')
                ->label('Documento')
                ->searchable()
                ->color('blue')
                ->openUrlInNewTab()
                ->url(function ($record): ?string {
                    if (!$record->ruta) return null;
                    
                    return URL::temporarySignedRoute(
                        'documentos.ver',
                        now()->addMinutes(1),
                        ['path' => $record->ruta]
                    );
                }),
                    
                TextColumn::make('created_at')
                ->label('Subido')
                ->dateTime('d/m/Y H:i')
                ->sortable(),
            ])
            ->recordActions([
                Action::make('asignar')
                ->label('Asignar legajo')
                ->icon('heroicon-o-link')
                ->schema(Documento::getFromSchemaRevisar())
                ->action(function (Documento $record, array $data): void {
                    $record->update([
                        'legajo_id' => $data['legajo_id'],
                        'descripcion' => $data['descripcion'],
                        'tipodoc' => $data['tipodoc'],
                    ]);
                }),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Todo en orden')
            ->emptyStateDescription('No hay documentos pendientes en asignar.');
    }
}
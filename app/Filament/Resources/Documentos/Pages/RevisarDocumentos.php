<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\DocumentoResource;
use App\Models\Documento;
use App\Models\Legajo;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use Filament\Navigation\NavigationItem;
use Illuminate\Support\Facades\URL;

class RevisarDocumentos extends Page implements HasTable
{
    use InteractsWithTable;
    protected static string $resource = DocumentoResource::class;
    protected string $view = 'filament.pages.revisar-documentos';
    public function table(Table $table): Table
    {
        return $table
            ->query(
                Documento::query()
                    ->whereNull('legajo_id') // agregá acá otras condiciones si hay más campos incompletos
            )
            ->columns([
                TextColumn::make('nombre_original')
                    ->label('Documento')
                    ->searchable()
                    ->color('blue')
                    ->openUrlInNewTab()
                    ->url(function ($record): ?string {
                        if (!$record->ruta) return null;
                        
                        return URL::temporarySignedRoute(
                            'documentos_revisar.ver',
                            now()->addMinutes(5),
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
                    ->schema([
                        Select::make('legajo_id')
                            ->label('Legajo')
                            ->options(Legajo::pluck('nombre', 'id')) // ajustá el campo según tu modelo
                            ->searchable()
                            ->required(),
                    ])
                    ->action(function (Documento $record, array $data): void {
                        $record->update(['legajo_id' => $data['legajo_id']]);
                    }),
                DeleteAction::make(),
            ])
            ->emptyStateHeading('Todo en orden')
            ->emptyStateDescription('No hay documentos pendientes en asignar.');
    }
}
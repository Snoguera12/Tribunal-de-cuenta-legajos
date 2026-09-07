<?php

namespace App\Filament\Resources\Documentos\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('legajo.num_legajo')
                ->label("Número de legajo")
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('ruta')
                ->label('Documento')
                //->formatStateUsing(fn () => 'Abrir Archivo')
                ->url(function ($record): ?string {
                    if (!$record->ruta) return null;
                    
                    return URL::temporarySignedRoute(
                        'documentos_revisar.ver',
                        now()->addMinutes(5),
                        ['path' => $record->ruta]
                    );
                })
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('tipodoc')
                ->label('Tipo de Documento')
                ->sortable()
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),
                /*TextColumn::make('fecha_de_creacion')
                ->label('Fecha de Creación')
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                ->label('Fecha de Subida')
                ->dateTime('d/m/Y H:i:s')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),*/
            ])
            ->filters([
                //
            ])
            ->recordActions([
                //EditAction::make(),
            ])
            ->toolbarActions([
                /*BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),*/
            ]);
    }
}

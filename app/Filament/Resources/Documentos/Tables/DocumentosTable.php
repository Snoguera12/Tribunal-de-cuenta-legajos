<?php

namespace App\Filament\Resources\Documentos\Tables;

use App\Filament\Actions\AsignarDocumentoAction;
use App\Models\Documento;
use Filament\Actions\EditAction;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(Documento::getOutSchema('table'))
            ->defaultSort('created_at', 'desc')
            ->filters([
                // por defecto solo los pendientes de completar; tambien se pueden ver los ya asignados
                SelectFilter::make('estado')
                    ->label('Mostrar')
                    ->options([
                        'pendientes' => 'Pendientes',
                        'asignados' => 'Ya asignados',
                        'todos' => 'Todos',
                    ])
                    ->default('pendientes')
                    ->selectablePlaceholder(false)
                    ->query(fn (Builder $query, array $data) => match ($data['value'] ?? 'pendientes') {
                        'asignados' => $query->whereNotNull('legajo_id')->whereNotNull('descripcion')->whereNotNull('tipodoc'),
                        'todos' => $query,
                        default => $query->pendientes(),
                    }),
            ])
            ->recordActions([
                AsignarDocumentoAction::make(),
                EditAction::make()
                ->label('Adjuntar'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

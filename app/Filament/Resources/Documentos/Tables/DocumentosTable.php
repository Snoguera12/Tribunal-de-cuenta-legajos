<?php

namespace App\Filament\Resources\Documentos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Storage;

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
                TextColumn::make('archivo')
                ->label('Documento')
                //->formatStateUsing(fn () => 'Abrir Archivo')
                ->url(fn (string $state): string => Storage::url($state))
                ->openUrlInNewTab()
                ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('tipodoc')
                    ->label('Tipo de Documento')
                    ->numeric()
                    ->sortable()
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn (int $state): string => match ($state) {
                    0 => 'DNI',
                    1 => 'Título',
                    2 => 'Cursos',
                    3 => 'Liciancia',
                    4 => 'Acta de Nacimiento',
                    5 => 'Certificado de Escolaridad',
                    6 => 'Certificado Defunción',
                    7 => 'Certificado de Casamiento',
                    8 => 'Sumario',
                    9 => 'Resolución',
                    10 => 'Foto de Perfil',
                    11 => 'Curriculum',
                    12 => 'Otro',
                    default => 'Desconocido',
                })
                ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('fecha_de_creacion')
                    ->label('Fecha de Creación')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                TextColumn::make('created_at')
                    ->label('Fecha de Subida')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

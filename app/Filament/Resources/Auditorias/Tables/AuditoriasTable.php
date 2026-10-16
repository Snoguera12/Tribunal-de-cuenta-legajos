<?php

namespace App\Filament\Resources\Auditorias\Tables;

use App\Models\Auditoria;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class AuditoriasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([

                TextColumn::make('created_at')
                ->label('Fecha')
                ->dateTime('d/m/Y H:i:s')
                ->sortable(),

                TextColumn::make('severidad')
                ->label('Nivel')
                ->badge()
                ->formatStateUsing(fn (string $state) => Auditoria::severidades()[$state] ?? $state)
                ->color(fn (string $state) => match ($state) {
                    'aviso' => 'warning',
                    'alta', 'critica' => 'danger',
                    default => 'gray',
                })
                ->sortable(),

                TextColumn::make('actor_nombre')
                ->label('Actor')
                ->placeholder('Sistema / anónimo')
                ->description(fn (Auditoria $registro) => $registro->actor_rol ?? $registro->actor_tipo)
                ->searchable(),

                TextColumn::make('evento')
                ->label('Evento')
                ->badge()
                ->formatStateUsing(fn (string $state) => Auditoria::eventos()[$state] ?? $state)
                ->color(fn (string $state) => match ($state) {
                    'creado', 'alta', 'login' => 'success',
                    'modificado', 'password_reset' => 'warning',
                    'eliminado', 'baja', 'login_fallido', 'acceso_denegado', 'api_denegado' => 'danger',
                    'token_generado', 'token_eliminado', 'exportacion', 'importacion' => 'info',
                    default => 'gray',
                })
                ->sortable(),

                TextColumn::make('descripcion')
                ->label('Descripción')
                ->limit(70)
                ->tooltip(fn (Auditoria $registro) => $registro->descripcion)
                ->searchable(),

                TextColumn::make('modelo')
                ->label('Registro')
                ->formatStateUsing(fn (Auditoria $registro) => $registro->modelo ? $registro->modelo . ' #' . $registro->modelo_id : '-')
                ->toggleable(),

                TextColumn::make('ip')
                ->label('IP')
                ->searchable()
                ->toggleable(),

                TextColumn::make('mac')
                ->label('MAC')
                ->placeholder('-')
                ->searchable()
                ->toggleable(),

                TextColumn::make('sistema_operativo')
                ->label('Equipo / navegador')
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('metodo')
                ->label('Método')
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('url')
                ->label('Página')
                ->limit(50)
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('navegador')
                ->label('Navegador')
                ->limit(40)
                ->toggleable(isToggledHiddenByDefault: true),

            ])
            ->filters([

                SelectFilter::make('evento')
                ->label('Evento')
                ->multiple()
                ->options(Auditoria::eventos()),

                SelectFilter::make('severidad')
                ->label('Nivel')
                ->multiple()
                ->options(Auditoria::severidades()),

                SelectFilter::make('categoria')
                ->label('Categoría')
                ->options(Auditoria::categorias()),

                SelectFilter::make('actor_id')
                ->label('Usuario')
                ->searchable()
                ->options(fn () => \App\Models\User::withTrashed()->orderBy('name')->pluck('name', 'id')->all())
                ->query(fn (Builder $query, array $data) => $query->when($data['value'] ?? null,
                    fn (Builder $q, $id) => $q->where('actor_tipo', 'usuario')->where('actor_id', $id))),

                SelectFilter::make('actor_tipo')
                ->label('Tipo de actor')
                ->options([
                    'usuario' => 'Usuario',
                    'api' => 'Cliente API',
                    'sistema' => 'Sistema',
                    'anonimo' => 'Anónimo',
                ]),

                SelectFilter::make('modelo')
                ->label('Tipo de registro')
                ->options(fn () => Auditoria::query()
                    ->whereNotNull('modelo')
                    ->distinct()
                    ->orderBy('modelo')
                    ->pluck('modelo', 'modelo')
                    ->toArray()),

                Filter::make('fecha')
                ->form([
                    DatePicker::make('desde')->label('Desde'),
                    DatePicker::make('hasta')->label('Hasta'),
                ])
                ->query(function (Builder $query, array $data): Builder {
                    return $query
                        ->when($data['desde'], fn (Builder $q, $fecha) => $q->whereDate('created_at', '>=', $fecha))
                        ->when($data['hasta'], fn (Builder $q, $fecha) => $q->whereDate('created_at', '<=', $fecha));
                }),

            ])
            ->recordActions([
                ViewAction::make()
                ->label('Ver')
                ->iconButton()
                ->color('success')
                ->icon('heroicon-m-eye'),
            ])
            ->toolbarActions([
                //
            ]);
    }
}

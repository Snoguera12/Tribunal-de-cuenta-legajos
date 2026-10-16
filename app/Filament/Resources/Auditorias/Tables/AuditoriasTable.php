<?php

namespace App\Filament\Resources\Auditorias\Tables;

<<<<<<< HEAD
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\Models\Activity;
use Filament\Tables\Table;

class AuditoriasTable
{
    private const ACCIONES = [
        'created' => 'Creación',
        'updated' => 'Modificación',
        'deleted' => 'Baja / Eliminación',
        'restored' => 'Restauración',
        'login' => 'Inicio de sesión',
        'logout' => 'Cierre de sesión',
    ];

=======
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
>>>>>>> version2-20261014_210855
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
<<<<<<< HEAD
                TextColumn::make('created_at')
                    ->label('Fecha y hora')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('causer.name')
                    ->label('Usuario')
                    ->default('Sistema (automático)')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('event')
                    ->label('Acción')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => self::ACCIONES[$state] ?? ($state ?? '—'))
                    ->color(fn (?string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        'restored' => 'info',
                        'login' => 'success',
                        'logout' => 'gray',
                        default => 'gray',
                    }),

                TextColumn::make('properties.ip')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('subject_type')
                    ->label('Tabla')
                    ->formatStateUsing(fn (?string $state): string => $state ? class_basename($state) : '—')
                    ->sortable(),

                TextColumn::make('subject_id')
                    ->label('Registro N°'),

                TextColumn::make('description')
                    ->label('Detalle')
                    ->limit(40),
            ])
            ->filters([
                SelectFilter::make('event')
                    ->label('Acción')
                    ->options(self::ACCIONES),

                SelectFilter::make('subject_type')
                    ->label('Tabla')
                    ->options(fn () => DB::table('activity_log')
                        ->whereNotNull('subject_type')
                        ->distinct()
                        ->pluck('subject_type', 'subject_type')
                        ->mapWithKeys(fn ($v, $k) => [$k => class_basename($k)])
                    ),

                // filtro de usuario hecho a mano (causer es relacion rara)
                SelectFilter::make('causer_id')
                    ->label('Usuario')
                    ->options(fn () => \App\Models\User::orderBy('name')->pluck('name', 'id'))
                    ->query(function ($query, array $data) {
                        if (filled($data['value'] ?? null)) {
                            $query->where('causer_id', $data['value'])
                                ->where('causer_type', \App\Models\User::class);
                        }
                    }),
            ])
            ->recordActions([
                Action::make('ver_cambios')
                    ->label('Ver cambios')
                    ->icon('heroicon-o-eye')
                    ->modalHeading('Detalle del cambio')
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Cerrar')
                    ->modalContent(fn (Activity $record) => new HtmlString(self::renderDiff($record)))
                    ->modalWidth('2xl'),
=======

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
>>>>>>> version2-20261014_210855
            ])
            ->toolbarActions([
                //
            ]);
    }
<<<<<<< HEAD

    // arma una tabla en html con lo que cambio (antes/despues)
    private static function renderDiff(Activity $record): string
    {
        $antes = $record->properties->get('old', collect());
        $despues = $record->properties->get('attributes', collect());

        if ($antes->isEmpty() && $despues->isEmpty()) {
            return '<p class="text-sm text-gray-500">No hay detalle de campos para esta entrada.</p>';
        }

        $campos = collect($despues->keys())->merge($antes->keys())->unique();

        $filas = $campos->map(function ($campo) use ($antes, $despues) {
            $valorAntes = e((string) ($antes[$campo] ?? '—'));
            $valorDespues = e((string) ($despues[$campo] ?? '—'));

            return <<<HTML
                <tr>
                    <td class="px-3 py-2 font-medium text-sm">{$campo}</td>
                    <td class="px-3 py-2 text-sm text-red-600">{$valorAntes}</td>
                    <td class="px-3 py-2 text-sm text-green-600">{$valorDespues}</td>
                </tr>
            HTML;
        })->implode('');

        return <<<HTML
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b">
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Campo</th>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Antes</th>
                        <th class="px-3 py-2 text-left text-xs uppercase text-gray-500">Después</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    {$filas}
                </tbody>
            </table>
        HTML;
    }
=======
>>>>>>> version2-20261014_210855
}

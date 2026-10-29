<?php

namespace App\Filament\Widgets;

use App\Filament\Resources\Personas\PersonaResource;
use App\Filament\Resources\Users\UserResource;
use App\Models\ActividadReciente;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

/**
 * Escritorio de RRHH y administradores: los ultimos agentes y usuarios (con su numero)
 * que esa misma persona vio o modifico, para volver a ellos con un clic.
 */
class UltimosAgentesWidget extends TableWidget
{
    protected static ?int $sort = -1;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Últimos agentes y usuarios que viste o modificaste';

    public const CANTIDAD = 10;

    public static function canView(): bool
    {
        return auth()->user()?->isAdmin_RRHH() ?? false;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(function () {
                // el administrador puede ver lo ultimo de TODO RRHH; los demas, solo lo propio
                $todos = auth()->user()?->isAdmin() && ($this->tableFilters['alcance']['value'] ?? 'mios') === 'todos';

                // primero los ids de los ultimos diez: asi el tope vale tambien para el conteo
                $ultimos = ActividadReciente::query()
                    ->when(! $todos, fn ($q) => $q->where('user_id', auth()->id()))
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id')
                    ->limit(self::CANTIDAD)
                    ->pluck('id');

                return ActividadReciente::query()
                    ->whereIn('id', $ultimos)
                    ->orderByDesc('updated_at')
                    ->orderByDesc('id');
            })
            ->filters([
                SelectFilter::make('alcance')
                    ->label('Ver')
                    ->options(['mios' => 'Solo lo mío', 'todos' => 'Todo RRHH (administrador)'])
                    ->default('mios')
                    ->selectablePlaceholder(false)
                    ->visible(fn () => auth()->user()?->isAdmin() ?? false)
                    ->query(fn ($query) => $query),
            ])
            ->columns([
                TextColumn::make('usuarioDeRrhh.name')
                    ->label('Hecho por')
                    ->visible(fn () => auth()->user()?->isAdmin() && ($this->tableFilters['alcance']['value'] ?? 'mios') === 'todos'),
                TextColumn::make('numero')
                    ->label('Número'),
                TextColumn::make('nombre')
                    ->label('Agente / usuario'),
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === ActividadReciente::TIPO_AGENTE ? 'Agente' : 'Usuario')
                    ->color(fn (string $state) => $state === ActividadReciente::TIPO_AGENTE ? 'info' : 'gray'),
                TextColumn::make('ultima_accion')
                    ->label('Última acción')
                    ->badge()
                    ->color(fn (string $state) => $state === 'Modificó' ? 'warning' : 'success'),
                TextColumn::make('visto_en')
                    ->label('Visto')
                    ->since()
                    ->placeholder('—'),
                TextColumn::make('modificado_en')
                    ->label('Modificado')
                    ->since()
                    ->placeholder('—'),
            ])
            ->recordUrl(fn (ActividadReciente $registro) => $registro->tipo === ActividadReciente::TIPO_AGENTE
                ? PersonaResource::getUrl('view', ['record' => $registro->registro_id])
                : UserResource::getUrl('view', ['record' => $registro->registro_id]))
            ->paginated(false)
            ->emptyStateHeading('Todavía no viste ni modificaste agentes')
            ->emptyStateDescription('Cuando abras o edites un agente, va a aparecer acá.');
    }
}

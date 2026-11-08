<?php

namespace App\Filament\Forms\Components;

use App\Models\Categoria;
use Closure;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Support\Enums\Size;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use BackedEnum;
use Carbon\CarbonInterface;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Support\Contracts\HasLabel;
use Illuminate\Support\HtmlString;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RepeaterAdjuntable extends Repeater
{
    protected bool $esAdjuntable = false;

    protected ?Closure $etiquetaAdjuntar = null;
    
    public function adjuntable(Closure $etiqueta): static
    {
        $this->esAdjuntable = true;
        $this->etiquetaAdjuntar = $etiqueta;

        $this->deleteAction(function (Action $action): Action {
            // ¿Este item ya está guardado en la base para este registro?
            $guardado = fn (Repeater $component, array $arguments): bool => $component
                ->getCachedExistingRecords()
                ->has($arguments['item'] ?? '');

            return $action
                ->label(fn (Repeater $component, array $arguments) => $guardado($component, $arguments)
                    ? 'Desadjuntar'
                    : 'Quitar')
                ->icon(fn (Repeater $component, array $arguments) => $guardado($component, $arguments)
                    ? 'heroicon-m-link-slash'
                    : 'heroicon-m-x-mark')
                ->color(fn (Repeater $component, array $arguments) => $guardado($component, $arguments)
                    ? 'warning'
                    : 'gray')
                ->requiresConfirmation(fn (Repeater $component, array $arguments) => $guardado($component, $arguments))
                ->modalHidden(fn (Repeater $component, array $arguments) => !$guardado($component, $arguments))
                ->modalHeading('¿Desadjuntar este registro?')
                ->modalDescription('No se borra: queda sin asignar y se puede volver a adjuntar.')
                ->modalSubmitActionLabel('Quitar')
                ->after(function (Repeater $component, array $arguments): void {
                    // Quitar un item que no estaba guardado no es un desadjuntado: no se avisa.
                    if (! $component->getCachedExistingRecords()->has($arguments['item'] ?? '')) {
                        return;
                    }

                    // Cuántos registros que están en la base ya no figuran en el formulario.
                    $estado = $component->getRawState() ?? [];

                    $pendientes = $component->getCachedExistingRecords()
                        ->keys()
                        ->reject(fn ($clave) => array_key_exists($clave, $estado))
                        ->count();

                    // Mismo id => cada aviso reemplaza al anterior, así nunca se acumulan.
                    Notification::make('desadjuntando-' . md5($component->getStatePath()))
                        ->title($pendientes === 1 ? '1 registro a desadjuntar' : "{$pendientes} registros a desadjuntar")
                        ->body('Presioná Guardar para confirmar.')
                        ->warning()
                        ->duration(5000)
                        ->send();
                });
        });

        $this->registerActions([
            fn (RepeaterAdjuntable $component): Action => $component->getAdjuntarAction(),
        ]);

        return $this;
    }

    public function getAddAction(): Action
    {
        if (! $this->esAdjuntable) {
            return parent::getAddAction();
        }

        $action = AccionAnadirConAdjuntar::make($this->getAddActionName())
            ->label(fn (Repeater $component) => $component->getAddActionLabel())
            ->color('gray')
            ->action(function (Repeater $component): void {
                $newUuid = $component->generateUuid();

                $items = $component->getRawState();

                if ($newUuid) {
                    $items[$newUuid] = [];
                } else {
                    $items[] = [];
                }

                $component->rawState($items);

                $component->getChildSchema($newUuid ?? array_key_last($items))->fill();

                $component->collapsed(false, shouldMakeComponentCollapsible: false);

                $component->callAfterStateUpdated();

                $component->shouldPartiallyRenderAfterActionsCalled() ? $component->partiallyRender() : null;
            })
            ->button()
            ->size(Size::Small)
            ->visible(fn (Repeater $component): bool => $component->isAddable());

        if ($this->modifyAddActionUsing) {
            $action = $this->evaluate($this->modifyAddActionUsing, [
                'action' => $action,
            ]) ?? $action;
        }

        return $action;
    }

    public function getAdjuntarAction(): Action
    {
        return Action::make('adjuntarExistente')
            ->label('Adjuntar uno ya existente')
            ->icon('heroicon-m-link')
            ->color('gray')
            ->button()
            ->size(Size::Small)
            ->visible(fn (): bool => (bool) auth()->user()?->isAdmin_RRHH())
            ->modalHeading('Adjuntar un registro existente')
            ->modalSubmitActionLabel('Adjuntar')
            ->schema(fn (Repeater $component): array => [
                Select::make('registros')
                ->label('Registros sin asignar')
                ->multiple()
                ->searchable()
                ->required()
                ->live()
                ->options(fn (): array => $component->getRegistrosSinAsignar()
                    ->mapWithKeys(fn (Model $r) => [
                        $r->getKey() => (string) (($this->etiquetaAdjuntar)($r) ?: "Registro #{$r->getKey()}"),
                    ])
                    ->all()),

                    Text::make(function (Get $get) use ($component): HtmlString {
                        $ids = $get('registros') ?? [];

                        $registros = $component->consultaSinAsignar(excluirEnFormulario: true)->whereKey($ids)->get();

                        if ($registros->isEmpty()) {
                            return new HtmlString('');
                        }

                        return new HtmlString(
                            '<div style="max-height:18rem;overflow:auto;display:grid;gap:.5rem;">'
                            . $registros->map(fn (Model $r) => $component->getDetalleRegistro($r)->toHtml())->implode('')
                            . '</div>'
                        );
                    }
                )->visible(fn (Get $get): bool => filled($get('registros'))),
            ])
            ->action(function (array $data, Repeater $component): void {
                $registros = $component->consultaSinAsignar(excluirEnFormulario: true)
                    ->whereKey($data['registros'])
                    ->get();

                // Solo se agregan al formulario. No se limpia la caché de registros existentes:
                // sirve para distinguir, al guardar, cuáles son nuevos.
                $estado = $component->getRawState() ?? [];

                foreach ($registros as $r) {
                    $estado["record-{$r->getKey()}"] = $r->attributesToArray();
                }

                $component->rawState($estado);

                // Hidratar cada item agregado (necesario para los campos de archivo).
                foreach ($registros as $r) {
                    foreach ($registros as $r) {
                        $component->getChildSchema("record-{$r->getKey()}")
                            ->model($r)
                            ->fill($r->attributesToArray());
                    }
                }

                Notification::make()
                    ->title($registros->count() === 1 ? 'Registro agregado' : "{$registros->count()} registros agregados")
                    ->body('Presioná Guardar para confirmar el adjuntado.')
                    ->warning()
                    ->send();
            });
    }

    protected function consultaSinAsignar(bool $excluirEnFormulario = false): Builder
    {
        $relacion = $this->getRelationship();
        $consulta = $relacion->getRelated()->newQuery();

        if ($relacion instanceof BelongsToMany) {
            // Todos los que todavía no están adjuntos a este registro.
            $clave = $relacion->getRelated()->getQualifiedKeyName();
            $consulta->whereNotIn($clave, $relacion->pluck($clave));
        } else {
            // Huérfanos: sin padre.
            $consulta->whereNull($relacion->getForeignKeyName());
        }

        // En pantalla, no ofrecer los que ya están agregados y pendientes de guardar.
        if ($excluirEnFormulario) {
            $consulta->whereKeyNot($this->idsEnFormulario());
        }

        return $consulta;
    }
    protected function idsEnFormulario(): array
    {
        return collect(array_keys($this->getRawState() ?? []))
            ->map(fn ($clave) => preg_match('/^record-(\d+)$/', (string) $clave, $m) ? (int) $m[1] : null)
            ->filter()
            ->values()
            ->all();
    }

    public function getRegistrosSinAsignar(): Collection
    {
        return $this->consultaSinAsignar(excluirEnFormulario: true)->get();
    }

    public function getDetalleRegistro(Model $registro): HtmlString
    {
        $relacion = $this->getRelationship();

        $ocultos = [$registro->getKeyName(), 'created_at', 'updated_at'];

        if (! $relacion instanceof BelongsToMany) {
            $ocultos[] = $relacion->getForeignKeyName();
        }

        $nombresCampo = [
            'titulo' => 'Título',
            'institucion' => 'Institución',
            'categoria' => 'Categoría',
            'area' => 'Área',
        ];

        $atributos = $registro->getAttributes();
        $filas = '';

        foreach (array_keys($atributos) as $campo) {
            if (in_array($campo, $ocultos, true)) {
                continue;
            }

            // "titulo_otro" / "institucion_otro" se muestran junto a su clave (titulo_id / institucion_id).
            if (str_ends_with($campo, '_otro')
                && array_key_exists(substr($campo, 0, -5) . '_id', $atributos)) {
                continue;
            }

            $valor = $registro->getAttribute($campo);

            // Si es una clave foránea (categoria_id, cargo_id, titulo_id...), mostrar el nombre.
            if (str_ends_with($campo, '_id')) {
                $nombreRelacion = substr($campo, 0, -3);

                if (method_exists($registro, $nombreRelacion)) {
                    $relacionado = $registro->{$nombreRelacion};

                    $valor = match (true) {
                        $relacionado instanceof Categoria => collect([$relacionado->nombre, $relacionado->descripcion])
                            ->filter()
                            ->implode(' – '),
                        default => $relacionado?->nombre ?? $relacionado?->descripcion ?? $valor,
                    };

                    // Sin registro del catálogo pero con texto escrito a mano: está en revisión.
                    $otro = $atributos["{$nombreRelacion}_otro"] ?? null;

                    if (blank($relacionado) && filled($otro)) {
                        $valor = trim((string) $otro) . ' - en revisión';
                    }

                    $campo = $nombreRelacion;
                }
            }

            $texto = match (true) {
                $valor === null || $valor === '' => '—',
                $valor instanceof HasLabel => (string) $valor->getLabel(),
                $valor instanceof BackedEnum => (string) $valor->value,
                $valor instanceof CarbonInterface => $valor->format('d/m/Y'),
                is_bool($valor) => $valor ? 'Sí' : 'No',
                default => (string) $valor,
            };

            $etiqueta = $nombresCampo[$campo] ?? (string) str($campo)->replace('_', ' ')->ucfirst();

            $filas .= '<div style="display:flex;gap:.5rem;padding:.15rem 0;">'
                . '<span style="font-weight:600;min-width:9rem;">' . e($etiqueta) . ':</span>'
                . '<span>' . e($texto) . '</span>'
                . '</div>';
        }

        return new HtmlString(
            '<div style="border:1px solid rgba(128,128,128,.35);border-radius:.5rem;padding:.75rem;">'
            . $filas
            . '</div>'
        );
    }
    public function saveToRelationship(): void
    {
        if ($this->esAdjuntable) {
            $relacion = $this->getRelationship();
            $state = $this->getState();
            $state = is_array($state) ? $state : [];

            // 1) Adjuntar los agregados con "Adjuntar existente":
            //    claves "record-ID" que todavía no están en la base como hijos de este registro.
            $existentes = $this->getCachedExistingRecords();

            $idsAAdjuntar = collect(array_keys($state))
                ->filter(fn ($clave) => is_string($clave)
                    && preg_match('/^record-\d+$/', $clave)
                    && ! $existentes->has($clave))
                ->map(fn (string $clave) => (int) substr($clave, 7))
                ->values();

            if ($idsAAdjuntar->isNotEmpty()) {
                abort_unless(auth()->user()?->isAdmin_RRHH(), 403);

                $registros = $this->consultaSinAsignar()->whereKey($idsAAdjuntar->all())->get();

                abort_unless(
                    $registros->count() === $idsAAdjuntar->count(),
                    409,
                    'Alguno de los registros ya no está disponible. Recargá la página.',
                );

                if ($relacion instanceof BelongsToMany) {
                    $relacion->attach($registros->modelKeys());
                } else {
                    $fk = $relacion->getForeignKeyName();
                    $padre = $this->getRecord()->getKey();

                    $registros->each(function (Model $r) use ($fk, $padre): void {
                        $r->setAttribute($fk, $padre);
                        $r->save();
                    });
                }

                $this->getRecord()->unsetRelation($this->getRelationshipName());
                $this->clearCachedExistingRecords();
                $existentes = $this->getCachedExistingRecords();
            }

            // 2) Desadjuntar los que se sacaron del formulario.
            $idsADesadjuntar = $existentes
                ->pluck($relacion->getRelated()->getKeyName())
                ->reject(fn ($id) => array_key_exists("record-{$id}", $state))
                ->all();

            if (filled($idsADesadjuntar)) {
                if ($relacion instanceof BelongsToMany) {
                    $relacion->detach($idsADesadjuntar);
                } else {
                    $fk = $relacion->getForeignKeyName();

                    $relacion->whereKey($idsADesadjuntar)->get()->each(function (Model $r) use ($fk): void {
                        $r->setAttribute($fk, null);
                        $r->save();
                    });
                }
            }
        }

        // Ya adjuntados y desadjuntados, el padre solo actualiza los datos de cada item.
        parent::saveToRelationship();
    }
}
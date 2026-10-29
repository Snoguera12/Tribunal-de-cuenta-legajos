<?php

namespace App\Filament\Actions;

use App\Enums\TipodocEnum;
use App\Models\Auditoria;
use App\Models\Documento;
use App\Models\Legajo;
use Filament\Actions\Action;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;

/**
 * Asigna un documento (un solo archivo) a una persona, a varias o a todas.
 * No se copia el archivo ni se crean documentos repetidos: cambian solo las asignaciones.
 */
class AsignarDocumentoAction extends Action
{
    public const UNO = 'uno';
    public const VARIOS = 'varios';
    public const TODOS = 'todos';

    public static function make(?string $name = null): static
    {
        return parent::make('asignar')
            ->label('Asignar')
            ->icon('heroicon-m-user-plus')
            ->color('primary')
            ->visible(fn () => auth()->user()?->isAdmin_RRHH() ?? false)
            ->modalHeading('Asignar documento')
            ->modalDescription(function (Documento $record): string {
                $texto = 'Un solo archivo para uno, varios o todos los agentes. No se hacen copias.';

                if ($record->dni_sugerido && $record->legajos()->doesntExist()) {
                    $sugerido = $record->legajoSugerido();
                    $texto .= $sugerido
                        ? ' El sistema de licencias indicó el DNI ' . $record->dni_sugerido . ' (' . $sugerido->persona->nombre . ' ' . $sugerido->persona->apellido . '): ya está elegido, confirmalo.'
                        : ' El sistema de licencias indicó el DNI ' . $record->dni_sugerido . ' pero no hay un legajo con ese DNI.';
                }

                return $texto;
            })
            ->modalSubmitActionLabel('Guardar asignación')
            ->fillForm(function (Documento $record): array {
                $actuales = $record->legajos()->pluck('legajos.id')->all();

                // sin asignar y con DNI indicado por la API: se propone ese agente
                if (! $actuales && ($sugerido = $record->legajoSugerido())) {
                    $actuales = [$sugerido->id];
                }

                return [
                    'destino' => count($actuales) > 1 ? self::VARIOS : self::UNO,
                    'legajos' => $actuales,
                    'tipodoc' => $record->tipodoc?->value,
                    'descripcion' => $record->descripcion,
                ];
            })
            ->form([
                Select::make('tipodoc')
                    ->label('Tipo de documento')
                    ->options(TipodocEnum::class)
                    ->native(false)
                    ->required(),

                Textarea::make('descripcion')
                    ->label('Descripción / notas')
                    ->required(),

                Radio::make('destino')
                    ->label('¿A quién se asigna?')
                    ->options([
                        self::UNO => 'A un agente',
                        self::VARIOS => 'A varios agentes',
                        self::TODOS => 'A todos los agentes (legajos de alta)',
                    ])
                    ->default(self::UNO)
                    ->live()
                    ->required(),

                Select::make('legajos')
                    ->label('Legajo/s')
                    ->multiple()
                    ->searchable()
                    ->preload(false)
                    ->maxItems(fn (Get $get) => $get('destino') === self::UNO ? 1 : null)
                    ->visible(fn (Get $get) => $get('destino') !== self::TODOS)
                    ->required(fn (Get $get) => $get('destino') !== self::TODOS)
                    ->getSearchResultsUsing(fn (string $search) => self::buscar($search))
                    ->getOptionLabelsUsing(fn (array $values) => Legajo::opcionesPorIds($values))
                    ->helperText('Buscá por nombre, apellido, DNI o número de legajo.'),
            ])
            ->action(function (array $data, Documento $record): void {
                $ids = $data['destino'] === self::TODOS
                    ? Legajo::query()->where('estado', true)->pluck('id')->all()
                    : array_values($data['legajos'] ?? []);

                $record->descripcion = $data['descripcion'];
                $record->tipodoc = $data['tipodoc'];
                $record->asignarA($ids, auth()->id());

                Auditoria::registrar(
                    evento: 'documento_asignado',
                    descripcion: 'Asignó un documento a ' . count($ids) . ' legajo/s',
                    modelo: $record,
                    extra: ['destino' => $data['destino'], 'cantidad' => count($ids)],
                );

                Notification::make()
                    ->title(count($ids) === 0 ? 'El documento volvió a pendientes' : 'Documento asignado a ' . count($ids) . ' legajo/s')
                    ->success()
                    ->send();
            });
    }

    private static function buscar(string $texto): array
    {
        $texto = trim($texto);

        return Legajo::query()
            ->with('persona')
            ->where(function ($q) use ($texto) {
                $q->where('num_legajo', 'like', "%{$texto}%")
                    ->orWhereHas('persona', fn ($p) => $p->where('nombre', 'like', "%{$texto}%")
                        ->orWhere('apellido', 'like', "%{$texto}%")
                        ->orWhere('dni', 'like', "%{$texto}%"));
            })
            ->orderByDesc('estado')
            ->limit(50)
            ->get()
            ->filter(fn ($legajo) => $legajo->persona)
            ->mapWithKeys(fn ($legajo) => [$legajo->id => Legajo::etiqueta($legajo)])
            ->all();
    }
}

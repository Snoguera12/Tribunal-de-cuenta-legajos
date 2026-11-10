<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Actions\MotivoBajaAction;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use App\Filament\Resources\Legajos\LegajoResource;
use App\Models\Auditoria;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Support\Htmlable;

class ViewLegajo extends ViewRecord
{
    protected static string $resource = LegajoResource::class;

    // las lecturas también se auditan
    public function mount(int | string $record): void
    {
        parent::mount($record);

        Auditoria::registrar(
            evento: 'ver_legajo',
            descripcion: 'Abrió el legajo Nº ' . $this->record->num_legajo,
            modelo: $this->record,
        );
    }

    protected function getHeaderActions(): array
    {
        return [
            $this->accionFichaPdf(),
            EditAction::make(),
            MotivoBajaAction::make(),
        ];
    }

    // Ficha del legajo en PDF. Necesita: composer require barryvdh/laravel-dompdf
    // (si el paquete no está instalado el botón no aparece)
    protected function accionFichaPdf(): Action
    {
        return Action::make('ficha_pdf')
            ->label('Descargar ficha (PDF)')
            ->icon(Heroicon::ArrowDownTray)
            ->color('gray')
            ->visible(fn () => class_exists(\Barryvdh\DomPDF\Facade\Pdf::class))
            ->action(function () {
                $legajo = $this->getRecord()->load([
                    'persona.estudios.estudioTitulos.titulo', 'persona.estudios.institucion',
                    'persona.cursos', 'persona.idiomas.idioma', 'persona.antecedentesLaborales', 'persona.familiares',
                    'cargo', 'area', 'categoria', 'historial',
                ]);

                Auditoria::registrar(
                    evento: 'exportacion',
                    descripcion: 'Descargó la ficha en PDF del legajo Nº ' . $legajo->num_legajo,
                    modelo: $legajo,
                );

                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.ficha-legajo', ['legajo' => $legajo]);

                return response()->streamDownload(
                    fn () => print($pdf->output()),
                    "legajo_{$legajo->num_legajo}.pdf",
                    ['Content-Type' => 'application/pdf'],
                );
            });
    }

    public function getTitle(): string|Htmlable
    {
        $legajo = $this->getRecord()->loadMissing('persona');
        $persona = $legajo->persona;

        $nombre = trim(($persona?->nombre ?? '') . ' ' . ($persona?->apellido ?? ''));

        return "Legajo de {$nombre} (DNI: {$persona?->dni} - NºLegajo: {$legajo->num_legajo})";
    }
}

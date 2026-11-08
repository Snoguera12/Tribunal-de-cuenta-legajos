<?php

namespace App\Filament\Resources\Personas\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Js;

class ViewPersona extends ViewRecord
{
    protected static string $resource = PersonaResource::class;

    // id del tab => [artículo, nombre]. Se cambia acá y se actualiza el botón.
    protected const EDICION_POR_TAB = [
        'persona'               => ['la', 'Información de la Persona'],
        'legajo'                => ['los', 'Legajos'],
        'estudio'               => ['los', 'Estudios y Títulos'],
        'curso'                 => ['los', 'Cursos'],
        'antecedenteslaborales' => ['los', 'Antecedentes Laborales'],
        'idioma'                => ['los', 'Idiomas'],
        'familiar'              => ['los', 'Familiares'],
        'usuario'               => ['el', 'Usuario'],
    ];

    public function getTitle(): string
    {
        $persona = $this->record;

        return "Perfil de {$persona->nombre} {$persona->apellido} (DNI: {$persona->dni})";
    }

    protected function getHeaderActions(): array
    {
        $edicion = Js::from(PersonaResource::getUrl('edit', ['record' => $this->record]));

        $etiquetas = collect(self::EDICION_POR_TAB)
            ->map(fn (array $t) => 'Editar ' . $t[0] . ' ' . $t[1])
            ->all();
        $primero = array_key_first($etiquetas);
        $etiquetasJs = Js::from($etiquetas);
        $primeroJs = Js::from($primero);

        $leerTab = "new URLSearchParams(window.location.search).get('tab') ?? {$primeroJs}";

        return [
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->label(new HtmlString('<span x-text="texto">' . e($etiquetas[$primero]) . '</span>'))
                ->extraAttributes([
                    'x-data' => "{ etiquetas: {$etiquetasJs}, tab: {$leerTab}, get texto() { return this.etiquetas[this.tab] ?? this.etiquetas[{$primeroJs}]; } }",
                    'x-on:click.window' => "setTimeout(() => { tab = {$leerTab}; }, 0)",
                    'onclick' => "event.preventDefault();"
                        . "const tab = new URLSearchParams(window.location.search).get('tab');"
                        . "window.location.href = {$edicion} + (tab ? '?tab=' + encodeURIComponent(tab) : '');",
                ]),
        ];
    }
}
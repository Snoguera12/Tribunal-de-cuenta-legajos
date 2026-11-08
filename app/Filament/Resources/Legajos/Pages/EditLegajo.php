<?php

namespace App\Filament\Resources\Legajos\Pages;

use App\Filament\Actions\MotivoBajaAction;
use App\Filament\Resources\Legajos\LegajoResource;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Js;

class EditLegajo extends EditRecord
{
    protected static string $resource = LegajoResource::class;
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (empty($data['fecha_de_ingreso'])) {
            $data['fecha_de_ingreso'] = now();
        }

        return $data;
    }
    protected function getHeaderActions(): array
    {
        $vista = Js::from(LegajoResource::getUrl('view', ['record' => $this->record]));

        return [
            ViewAction::make()
            ->extraAttributes([
                'onclick' => "event.preventDefault();"
                    . "const tab = new URLSearchParams(window.location.search).get('tab');"
                    . "window.location.href = {$vista} + (tab ? '?tab=' + encodeURIComponent(tab) : '');",
            ]),
            MotivoBajaAction::make()
        ];
    }
}

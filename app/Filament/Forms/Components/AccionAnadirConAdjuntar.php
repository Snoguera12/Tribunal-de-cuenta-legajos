<?php

namespace App\Filament\Forms\Components;

use Filament\Actions\Action;

class AccionAnadirConAdjuntar extends Action
{
    public function toHtml(): string
    {
        $anadir = parent::toHtml();

        $adjuntar = $this->getSchemaComponent()?->getAction('adjuntarExistente');

        if (!$adjuntar || !$adjuntar->isVisible()) {
            return $anadir;
        }

        return '<div class="flex flex-wrap items-center" style="gap: 1.5rem;">'
            . $anadir
            . $adjuntar->toHtml()
            . '</div>';
    }
}
<?php

namespace App\Filament\Resources\Legajos\Schemas;

use App\Models\Legajo;
use Filament\Schemas\Schema;

class LegajoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components(Legajo::getFrontLegajoDocumentos('solo'));
    }
}
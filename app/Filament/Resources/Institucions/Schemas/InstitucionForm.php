<?php

namespace App\Filament\Resources\Institucions\Schemas;

use App\Models\Institucion;
use Filament\Schemas\Schema;

class InstitucionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components(Institucion::getAbmFormSchema());
    }
}

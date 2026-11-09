<?php

namespace App\Filament\Resources\Idiomas\Schemas;

use App\Models\Idioma;
use Filament\Schemas\Schema;

class IdiomaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(Idioma::getFormSchema('table'));
    }
}
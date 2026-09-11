<?php

namespace App\Filament\Resources\Titulos\Schemas;

use App\Models\Titulo;
use Filament\Schemas\Schema;

class TituloForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(Titulo::getFormSchema(true));
    }
}

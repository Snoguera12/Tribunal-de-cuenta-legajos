<?php

namespace App\Filament\Resources\Areas\Schemas;

use App\Models\Area;
use Filament\Schemas\Schema;

class AreaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(Area::getFormSchema());
    }
}

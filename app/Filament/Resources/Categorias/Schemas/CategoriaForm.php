<?php

namespace App\Filament\Resources\Categorias\Schemas;

use App\Models\Categoria;
use Filament\Schemas\Schema;

class CategoriaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(Categoria::getFormSchema());
    }
}
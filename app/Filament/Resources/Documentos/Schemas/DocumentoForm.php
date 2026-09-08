<?php

namespace App\Filament\Resources\Documentos\Schemas;

use App\Models\Documento;
use Filament\Schemas\Schema;


class DocumentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema->components(Documento::getFromSchema(false, true));
    }
}

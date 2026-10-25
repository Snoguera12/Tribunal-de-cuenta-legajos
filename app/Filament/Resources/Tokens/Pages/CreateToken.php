<?php

namespace App\Filament\Resources\Tokens\Pages;

use App\Enums\TokenDuracion;
use App\Filament\Resources\Tokens\TokenResource;
use Filament\Resources\Pages\CreateRecord;

class CreateToken extends CreateRecord
{
    protected static string $resource = TokenResource::class;
}

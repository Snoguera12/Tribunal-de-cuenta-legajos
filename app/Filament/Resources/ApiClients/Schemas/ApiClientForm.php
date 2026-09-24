<?php

namespace App\Filament\Resources\ApiClients\Schemas;

use App\Models\ApiClient;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class ApiClientForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                ->label("Nombre")
                ->required(),

                /*Toggle::make('is_active')
                ->label('Estado')
                ->visible(fn (?ApiClient $record) => $record?->is_token)*/
                
            ]);
    }
}

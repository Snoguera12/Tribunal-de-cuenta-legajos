<?php

namespace App\Filament\Resources\ApiClients;

use App\Filament\Resources\ApiClients\Pages\ListApiClients;
use App\Filament\Resources\ApiClients\Schemas\ApiClientForm;
use App\Filament\Resources\ApiClients\Tables\ApiClientsTable;
use App\Models\ApiClient;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ApiClientResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';
    protected static ?string $modelLabel = "Tokens";
    protected static ?int $navigationSort = 4;
    protected static ?string $model = ApiClient::class;

    public static function form(Schema $schema): Schema
    {
        return ApiClientForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ApiClientsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApiClients::route('/'),
            //'create' => CreateApiClient::route('/create'),
            //'edit' => EditApiClient::route('/{record}/edit'),
        ];
    }
}

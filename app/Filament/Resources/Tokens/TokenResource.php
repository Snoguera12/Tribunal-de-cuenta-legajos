<?php

namespace App\Filament\Resources\Tokens;

use App\Filament\Resources\Tokens\Pages\ListTokens;
use App\Filament\Resources\Tokens\Schemas\TokenForm;
use App\Filament\Resources\Tokens\Tables\TokensTable;
use App\Models\Token;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class TokenResource extends Resource
{
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-key';
    protected static ?string $modelLabel = "Tokens";
    protected static ?int $navigationSort = 4;
    protected static ?string $model = Token::class;

    public static function form(Schema $schema): Schema
    {
        return TokenForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TokensTable::configure($table);
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
            'index' => ListTokens::route('/'),
        ];
    }
}

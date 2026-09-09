<?php

namespace App\Filament\Resources\Users\Tables;

use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns(User::getOutSchema('table'))
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make()
                    ->label('Editar')
                    ->iconButton()
                    ->color('primary')
                    ->icon('heroicon-m-pencil-square'),
            ])
            ->toolbarActions([
                /*BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),*/
            ]);
    }
}

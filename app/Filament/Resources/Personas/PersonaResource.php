<?php

namespace App\Filament\Resources\Personas;

use App\Filament\Resources\Personas\Pages\CreatePersona;
use App\Filament\Resources\Personas\Pages\EditPersona;
use App\Filament\Resources\Personas\Pages\ListPersonas;
use App\Filament\Resources\Personas\Pages\ViewPersona;
use App\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Filament\Resources\Personas\Schemas\PersonaInfolist;
use App\Filament\Resources\Personas\Tables\PersonasTable;
use App\Models\Persona;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PersonaResource extends Resource
{
    protected static ?string $model = Persona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUser;
    protected static ?string $modelLabel = "información de Agentes";
    protected static ?int $navigationSort = 1;
    
    public static function getNavigationBadgeColor(): string|array|null{
        return "success";
    }
    public static function form(Schema $schema): Schema
    {
        return PersonaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonasTable::configure($table);
    }
    // el empleado solo ve su propia persona
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->isEmpleado()) {
            return $query->where('id', $user->persona_id);
        }

        return $query;
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function shouldRegisterNavigation(): bool
    {
        // en el menu solo para administrador, RRHH y funcionario
        return auth()->user()->isStaffRoles();
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonas::route('/'),
            'create' => CreatePersona::route('/añadir'),
            'view' => ViewPersona::route('/{record}'),
            'edit' => EditPersona::route('/{record}/editar'),
        ];
    }
}

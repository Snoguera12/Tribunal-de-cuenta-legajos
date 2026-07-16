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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;
    //protected static ?string $recordTitleAttribute = 'nombre'; 
    //protected static string|\UnitEnum|null $navigationGroup = "Agentes";
    protected static ?string $modelLabel = "información de Agentes";
    protected static ?int $navigationSort = 1;
    
    /*public static function getNavigationBadge(): ?string{
        return Persona::count();
    }*/
    
    public static function getNavigationBadgeColor(): string|array|null{
        return "succes";
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
    // Esta función hace que solo se puede ver los datos a su respectivo empleados.
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
        // Solo se muestra el acceso al recurso completo en la barra lateral a Administrador, RRHH y el Funcionario.
        return auth()->user()->isAdmin_RRHH_Funcionario();
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

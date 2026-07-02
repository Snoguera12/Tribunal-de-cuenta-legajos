<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Personas\PersonaResource;
use App\Models\User;
use BackedEnum;
use Filament\Forms\Contracts\HasForms;
use Filament\Pages\Page;

class MisDatosPersonales extends Page
{
    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-user-circle';

    protected static ?string $navigationLabel = 'Mis Datos Personales';
    protected ?string $heading = 'Mis Datos Personales';
    public static function canAccess(): bool
    {
        // Return true if the user is authorized, or use a Spatie permission check
        return true;
    }
    public static function shouldRegisterNavigation(): bool
    {
        // Solo se muestra el acceso al recurso completo en la barra lateral a Administrador, RRHH y el Funcionario.
        return auth()->user()->isAdmin_RRHH();
    }
    public static function getNavigationUrl(): string
    {
        $usuario = auth()->user();

        // Si el usuario no tiene una persona vinculada (es null o está vacío), va a la creación
        if (!$usuario || ! $usuario->persona_id) {
            return PersonaResource::getUrl('create');
        }

        // Si ya la tiene, va a la vista de lectura
        return PersonaResource::getUrl('view', ['record' => $usuario->persona_id]);
    }

}

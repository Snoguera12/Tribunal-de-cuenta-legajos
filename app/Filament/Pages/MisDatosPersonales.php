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
    protected static ?int $navigationSort = 1;
    public static function canAccess(): bool
    {
        // todos los roles pueden entrar a esta pagina
        return true;
    }
    public static function shouldRegisterNavigation(): bool
    {
        // en el menu para administrador, RRHH y empleados con persona
        return auth()->user()->isAdmin_RRHH() || auth()->user()->persona_id != NULL;
    }
    public static function getNavigationUrl(): string
    {
        $usuario = auth()->user();

        // sin persona vinculada: va a crearla
        if (!$usuario || ! $usuario->persona_id) {
            return PersonaResource::getUrl('create', ['propia' => 1]);
        }

        // con persona: va a verla
        return PersonaResource::getUrl('view', ['record' => $usuario->persona_id]);
    }

}

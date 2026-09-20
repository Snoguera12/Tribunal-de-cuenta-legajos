<?php

namespace App\Models\Helpers;

class UserHelper
{
    public static function getFormatStateRoles(string $state): string
    {
        $resulado = match ($state) {
            'empleado' => 'Empleado',
            'funcionario' => 'Funcionario',
            'rrhh' => 'Recursos Humanos',
            'administrador' => 'Administrador',
            default => 'Desconocido',
        };

        return $resulado;
    }

    public static function getOpcionesRoles()
    {
        $resultado = [];
        if(auth()->user()->isAdmin_RRHH()){
            $resultado = [
                'empleado' => 'Empleado',
                'funcionario' => 'Funcionario',
            ];
            
            if(auth()->user()->isAdmin()){
                $resultado = [
                    ...$resultado,
                    'rrhh' => 'Recursos Humanos',
                    'administrador' => 'Administrador',
                ];
            }
        }

        return $resultado;
    }
    public static function getShouldVisibleField($record): bool
    {
        if(isset($record->id) == null) return true;

        if (auth()->user()->isAdmin_RRHH()){
            if($record->id === auth()->user()->id){
                return false;
            }
        }

        return true;
    }
    //fn (string $record): bool => UserPermissionsHelper::getShouldDisableField($record)
    public static function getShouldDisableField($record): bool
    {
        if(isset($record->id) == null) return false;

        if (auth()->user()->isAdmin_RRHH()){
            if($record->id === auth()->user()->id){
                return true;
            }
        }

        return false;
    }
}

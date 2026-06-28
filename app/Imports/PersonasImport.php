<?php

namespace App\Imports;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class PersonasImport implements ToModel, WithHeadingRow, WithValidation
{
    public function model(array $row): Persona
    {
        $persona = Persona::create([
            'nombre'              => $row['nombre'],
            'apellido'            => $row['apellido'],
            'dni'                 => $row['dni'],
            'cuil'                => $row['cuil'],
            'email'               => $row['email'],
            'genero'              => $row['genero'],
            'estado_civil'        => $row['estado_civil'],
            'fecha_de_nacimiento' => $row['fecha_de_nacimiento'],
            'domicilio'           => $row['domicilio'] ?? '',
            'telefono'            => $row['telefono'] ?? '',
            'telefono_emergencia' => $row['telefono_emergencia'] ?? '',
        ]);

        // Crear usuario vinculado automáticamente
        User::create([
            'name'               => $persona->nombre . ' ' . $persona->apellido,
            'email'              => $persona->email,
            'password'           => Hash::make('Tcse2026!'),
            'rol'                => 1,
            'persona_id'         => $persona->id,
            'email_verified_at'  => now(),
        ]);

        return $persona;
    }

    public function rules(): array
    {
        return [
            'nombre'              => 'required|string',
            'apellido'            => 'required|string',
            'dni'                 => 'required|numeric|unique:personas,dni',
            'cuil'                => 'required|numeric|unique:personas,cuil',
            'email'               => 'required|email|unique:personas,email|unique:users,email',
            'genero'              => 'required|numeric',
            'estado_civil'        => 'required|numeric',
            'fecha_de_nacimiento' => 'required|date',
        ];
    }
}   }
}
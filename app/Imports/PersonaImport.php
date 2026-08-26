<?php

namespace App\Imports;

use App\Models\Persona;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
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

        $passwordTemporal = Str::password(14);

        User::create([
            'name'               => $persona->nombre . ' ' . $persona->apellido,
            'email'              => $persona->email,
            'password'           => Hash::make($passwordTemporal),
                     'rol'                => 1,
                     'persona_id'         => $persona->id,
                     'email_verified_at'  => now(),
        ]);

        Password::sendResetLink(['email' => $persona->email]);

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
}
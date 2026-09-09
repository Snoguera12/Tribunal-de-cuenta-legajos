<?php

namespace Database\Seeders;

use App\Models\Persona;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PersonaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Persona::create(
            [
                'nombre' => 'Roberto',
                'apellido' => 'Suárez',
                'dni' => 29458741,
                'cuil' => 20294587419,
                'email' => 'robertosuarez@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1982-04-11',
                'domicilio' => 'Av. Belgrano 450, Santiago del Estero',
                'telefono' => '3854512345',
                'telefono_emergencia' => '3854567890',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Valentina',
                'apellido' => 'Acosta',
                'dni' => 31685499,
                'cuil' => 27316854992,
                'email' => 'valentinaacosta@gmail.com',
                'genero' => 0,
                'estado_civil' => 0,
                'fecha_de_nacimiento' => '1996-06-23',
                'domicilio' => 'Pasaje Los Álamos 120, La Banda',
                'telefono' => '3855123456',
                'telefono_emergencia' => '3855654321',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Facundo',
                'apellido' => 'Ledesma',
                'dni' => 33658941,
                'cuil' => 20336589412,
                'email' => 'facundoledesma@gmail.com',
                'genero' => 1,
                'estado_civil' => 3,
                'fecha_de_nacimiento' => '1965-01-30',
                'domicilio' => 'Calle San Martín 890, Santiago del Estero',
                'telefono' => '3854987654',
                'telefono_emergencia' => '3854123789',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Camila',
                'apellido' => 'Paz',
                'dni' => 37841223,
                'cuil' => 27378412232,
                'email' => 'camilapaz@gmail.com',
                'genero' => 0,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1991-09-17',
                'domicilio' => 'Barrio Parque 34, Santiago del Estero',
                'telefono' => '3856012345',
                'telefono_emergencia' => '3856098765',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Nicolás',
                'apellido' => 'Ávila',
                'dni' => 27481233,
                'cuil' => 20274812332,
                'email' => 'nicolasavila@gmail.com',
                'genero' => 1,
                'estado_civil' => 2,
                'fecha_de_nacimiento' => '1978-12-05',
                'domicilio' => 'Ruta 9 Km 3, La Banda',
                'telefono' => '3854334455',
                'telefono_emergencia' => '3854556677',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Micaela',
                'apellido' => 'Ibáñez',
                'dni' => 29311488,
                'cuil' => 27293114882,
                'email' => 'micaelaibanez@gmail.com',
                'genero' => 0,
                'estado_civil' => 0,
                'fecha_de_nacimiento' => '1999-03-14',
                'domicilio' => 'Av. Roca 210, Santiago del Estero',
                'telefono' => '3857112233',
                'telefono_emergencia' => '3857445566',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Alejandro',
                'apellido' => 'Coria',
                'dni' => 40985477,
                'cuil' => 20409854772,
                'email' => 'alejandrocoria@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1970-07-28',
                'domicilio' => 'Barrio Autonomía 56, Santiago del Estero',
                'telefono' => '3854778899',
                'telefono_emergencia' => '3854990011',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Rocío',
                'apellido' => 'Juárez',
                'dni' => 42336884,
                'cuil' => 27423368842,
                'email' => 'rociojuarez@gmail.com',
                'genero' => 2,
                'estado_civil' => 0,
                'fecha_de_nacimiento' => '1993-10-02',
                'domicilio' => 'Pje. San José 78, La Banda',
                'telefono' => '3855667788',
                'telefono_emergencia' => '3855889900',
                'borrar_logico' => false,
            ],
        );
        Persona::create(
            [
                'nombre' => 'Mario',
                'apellido' => 'Santos',
                'dni' => 26146985,
                'cuil' => 20261469852,
                'email' => 'mariosantos1@gmail.com',
                'genero' => 1,
                'estado_civil' => 3,
                'fecha_de_nacimiento' => '1991-04-03',
                'domicilio' => 'Belgrano 123',
                'telefono' => '3855237845',
                'telefono_emergencia' => '3855114436',
            ],
            
        );
    }
}
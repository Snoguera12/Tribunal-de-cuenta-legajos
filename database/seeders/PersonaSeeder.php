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
                'nombre' => 'Pedro',
                'apellido' => 'García',
                'dni' => 10000001,
                'cuil' => 20100000012,
                'email' => 'pedrogarcia@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1980-05-15',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create([
                'nombre' => 'Georgi',
                'apellido' => 'Facello',
                'dni' => 10000002,
                'cuil' => 20100000022,
                'email' => 'georgi@gmail.com',
                'genero' => 1,
                'estado_civil' => 2,
                'fecha_de_nacimiento' => '1953-09-02',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ]
        );
        Persona::create(
            [
                'nombre' => 'María',
                'apellido' => 'López',
                'dni' => 10000003,
                'cuil' => 20100000032,
                'email' => 'marialopez@gmail.com',
                'genero' => 0,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1977-08-22',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Jorge',
                'apellido' => 'Martínez',
                'dni' => 10000004,
                'cuil' => 20100000042,
                'email' => 'jorgemartinez@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1990-12-01',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Lucía',
                'apellido' => 'Gómez',
                'dni' => 10000005,
                'cuil' => 20100000052,
                'email' => 'luciagomez@gmail.com',
                'genero' => 0,
                'estado_civil' => 2,
                'fecha_de_nacimiento' => '1985-11-09',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Carlos',
                'apellido' => 'Hernández',
                'dni' => 10000006,
                'cuil' => 20100000062,
                'email' => 'carloshernandez@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1972-03-30',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Ana',
                'apellido' => 'Rodríguez',
                'dni' => 10000007,
                'cuil' => 20100000072,
                'email' => 'anarodriguez@gmail.com',
                'genero' => 0,
                'estado_civil' => 0,
                'fecha_de_nacimiento' => '1995-07-07',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Luis',
                'apellido' => 'Fernández',
                'dni' => 10000008,
                'cuil' => 20100000082,
                'email' => 'luisfernandez@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1988-01-17',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Elena',
                'apellido' => 'Pérez',
                'dni' => 10000009,
                'cuil' => 20100000092,
                'email' => 'elenaperez@gmail.com',
                'genero' => 0,
                'estado_civil' => 2,
                'fecha_de_nacimiento' => '1983-02-25',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Santiago',
                'apellido' => 'Sánchez',
                'dni' => 10000010,
                'cuil' => 20100000102,
                'email' => 'santiagosanchez@gmail.com',
                'genero' => 1,
                'estado_civil' => 0,
                'fecha_de_nacimiento' => '1992-09-10',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Marta',
                'apellido' => 'Ramírez',
                'dni' => 10000011,
                'cuil' => 20100000112,
                'email' => 'martaramirez@gmail.com',
                'genero' => 0,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1989-04-14',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Fernando',
                'apellido' => 'Flores',
                'dni' => 10000012,
                'cuil' => 20100000122,
                'email' => 'fernandoflores@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1988-05-12',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Marta',
                'apellido' => 'Hernández',
                'dni' => 10000013,
                'cuil' => 20100000132,
                'email' => 'martahernandez@gmail.com',
                'genero' => 0,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1985-08-10',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
        Persona::create(
            [
                'nombre' => 'Diego',
                'apellido' => 'Hernández',
                'dni' => 10000014,
                'cuil' => 20100000142,
                'email' => 'diegohernandez@gmail.com',
                'genero' => 1,
                'estado_civil' => 1,
                'fecha_de_nacimiento' => '1989-03-18',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
                'borrar_logico' => true,
            ],
            
        );
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
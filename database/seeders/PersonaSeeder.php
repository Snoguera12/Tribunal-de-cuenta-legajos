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
        /*Persona::create(
            [
                'nombre' => '',
                'apellido' => '',
                'dni' => ,
                'cuil' => ,
                'email' => '',
                'genero' => ,
                'estado_civil' => ,
                'fecha_de_nacimiento' => '',
                'domicilio' => '',
                'telefono' => '',
                'telefono_emergencia' => '',
            ],
            
        );*/
        Persona::create([
                'nombre' => 'Georgi',
                'apellido' => 'Facello',
                'dni' => 10000001,
                'cuil' => 20100000012,
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
                'nombre' => 'Pedro',
                'apellido' => 'García',
                'dni' => 10000002,
                'cuil' => 20100000022,
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
    }
}

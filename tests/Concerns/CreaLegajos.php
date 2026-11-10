<?php

namespace Tests\Concerns;

use App\Enums\EstadoLegajoEnum;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;

// datos de prueba compartidos por los tests
trait CreaLegajos
{
    private static int $contadorDePrueba = 0;

    // crea una persona con su legajo de alta; $persona y $legajo pisan los valores por defecto
    protected function crearLegajo(array $persona = [], array $legajo = []): Legajo
    {
        $n = ++self::$contadorDePrueba;

        $persona = Persona::create(array_merge([
            'nombre' => 'Ana',
            'apellido' => 'Pérez',
            'dni' => 30000000 + $n,
            'cuil' => 20300000000 + $n,
            'email' => "persona{$n}@example.com",
            'genero' => 1,
            'estado_civil' => 1,
            'fecha_de_nacimiento' => '1990-01-01',
        ], $persona));

        return Legajo::create(array_merge([
            'num_legajo' => 1000 + $n,
            'estado' => EstadoLegajoEnum::Alta,
            'fecha_de_ingreso' => now(),
            'persona_id' => $persona->id,
            'categoria_id' => Categoria::create(['nombre' => "CAT {$n}", 'descripcion' => 'Prueba'])->id,
            'cargo_id' => Cargo::create(['nombre' => "Cargo {$n}"])->id,
            'area_id' => Area::create(['nombre' => "Área {$n}"])->id,
        ], $legajo));
    }

    protected function usuario(string $rol, ?int $personaId = null): User
    {
        return User::factory()->create(['rol' => $rol, 'persona_id' => $personaId]);
    }

    // un PDF mínimo pero con la estructura completa (cabecera y %%EOF)
    protected function pdfDePrueba(string $extra = ''): string
    {
        return "%PDF-1.4\n1 0 obj<</Type/Catalog>>endobj\n{$extra}\n%%EOF\n";
    }
}

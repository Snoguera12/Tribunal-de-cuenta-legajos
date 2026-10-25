<?php

namespace App\Services;

use Filament\Forms\Components\Field;
use Filament\Infolists\Components\Entry;
use Filament\Tables\Columns\Column;
use Filament\Tables\Filters\BaseFilter;

/**
 * Rótulos en español en un solo lugar.
 *
 * Filament arma el rótulo de un campo a partir de su nombre ("num_legajo" => "Num legajo",
 * "dni" => "Dni", "telefono" => "Telefono"). Este mapa corrige esos casos para todos los
 * formularios, tablas, vistas de lectura y filtros.
 *
 * Solo se aplica a los componentes que NO tienen ->label() propio: si un campo ya define su
 * rótulo en el código, ese gana. Para corregir otro rótulo, se agrega su nombre acá.
 */
class Etiquetas
{
    public const MAPA = [
        // Persona
        'dni' => 'DNI',
        'cuil' => 'CUIL',
        'genero' => 'Género',
        'telefono' => 'Teléfono',
        'telefono_emergencia' => 'Teléfono de emergencia',
        'persona_id' => 'Persona',
        'persona.dni' => 'DNI',
        'persona.nivel_estudio' => 'Nivel de estudio',

        // Legajo
        'num_legajo' => 'N° de legajo',
        'tipo_contrato' => 'Tipo de contrato',
        'fecha_baja' => 'Fecha de baja',
        'legajo_id' => 'Legajo',
        'legajo.num_legajo' => 'N° de legajo',
        'legajo.persona.nombre' => 'Nombre',
        'legajo.persona.apellido' => 'Apellido',
        'legajo.persona.dni' => 'DNI',
        'cargo_id' => 'Cargo',
        'cargo.nombre' => 'Cargo',
        'area_id' => 'Área',
        'area' => 'Área',
        'area.nombre' => 'Área',
        'categoria' => 'Categoría',
        'categoria_id' => 'Categoría',
        'categoria.nombre' => 'Categoría',

        // Documentos
        'tipodoc' => 'Tipo de documento',
        'descripcion' => 'Descripción',

        // Estudios, títulos, cursos e instituciones
        'nivel_estudio' => 'Nivel de estudio',
        'titulo_id' => 'Título',
        'titulo_otro' => 'Título (escrito a mano)',
        'institucion' => 'Institución',
        'institucion_id' => 'Institución',
        'institucion_otro' => 'Institución (escrita a mano)',
        'institucion_mostrada' => 'Institución',
        'nombre_mostrado' => 'Título',
        'duracion' => 'Duración',
        'estudios_count' => 'Estudios',
        'cursos_count' => 'Cursos',
        'cargos_count' => 'Cargos',
        'legajos_count' => 'Legajos',

        // Antecedentes laborales y fechas
        'fecha_inicio' => 'Fecha de inicio',
        'fecha_fin' => 'Fecha de fin',
        'fecha_de_creacion' => 'Fecha de creación',
        'motivo_egreso' => 'Motivo de egreso',

        // Licencias (las carga el Sistema de Licencias)
        'tipo_licencia' => 'Tipo de licencia',
        'dias_solicitados' => 'Días solicitados',
        'fecha_aprobacion' => 'Fecha de aprobación',
        'nro_resolucion' => 'N° de resolución',

        // Usuarios y clientes de API
        'name' => 'Nombre',
        'password' => 'Contraseña',
        'is_active' => 'Activo',
        'email_verified_at' => 'Correo verificado',
        'created_at' => 'Creado',
        'updated_at' => 'Modificado',
    ];

    public static function registrar(): void
    {
        $aplicar = function ($componente): void {
            $nombre = $componente->getName();

            if (is_string($nombre) && array_key_exists($nombre, self::MAPA)) {
                $componente->label(self::MAPA[$nombre]);
            }
        };

        // configureUsing sobre la clase base alcanza a todos sus tipos (TextInput, Select, TextColumn, TextEntry...).
        // Corre al crear el componente, antes de sus propios métodos, por eso un ->label() propio siempre gana.
        Field::configureUsing($aplicar);
        Column::configureUsing($aplicar);
        Entry::configureUsing($aplicar);
        BaseFilter::configureUsing($aplicar);
    }
}

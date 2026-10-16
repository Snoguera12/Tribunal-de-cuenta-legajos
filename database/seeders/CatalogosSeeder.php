<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Oficina;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;


// areas, cargos, categorias y oficinas (se puede correr varias veces)
class CatalogosSeeder extends Seeder
{

    public const AREAS = [
        'Presidencia', 'Secretaría General', 'Secretaría Contable', 'Auditoría de Cuentas',
        'Fiscalización de Obras Públicas', 'Asesoría Legal', 'Recursos Humanos', 'Sistemas e Informática',
        'Mesa de Entradas', 'Tesorería y Contaduría', 'Archivo General', 'Capacitación y Estudios',
        'Prensa y Ceremonial', 'Servicios Generales', 'Sumarios y Responsabilidades',
    ];

    public const CARGOS = [
        'Presidente', 'Vocal', 'Secretario General', 'Secretario Contable', 'Director de Auditoría',
        'Auditor Contable', 'Auditor Fiscal', 'Contador Fiscal', 'Abogado Dictaminante', 'Procurador',
        'Jefe de Recursos Humanos', 'Liquidador de Haberes', 'Analista de Sistemas', 'Soporte Técnico',
        'Jefe de Mesa de Entradas', 'Administrativo', 'Auxiliar Administrativo', 'Tesorero', 'Archivista',
        'Capacitador', 'Periodista', 'Ceremonial', 'Chofer', 'Ordenanza', 'Mantenimiento', 'Instructor Sumariante',
    ];

    public const CATEGORIAS = [
        'PS' => 'Personal Superior',
        'PROF' => 'Profesional',
        'TEC' => 'Técnico',
        'ADM' => 'Administrativo',
        'MAE' => 'Maestranza y Servicios',
    ];


    // sin eventos: es el estado inicial, no hace falta llenar la auditoria con cada catalogo
    public function run(): void
    {
        Model::withoutEvents(fn () => $this->cargar());
    }


    private function cargar(): void
    {
        $columnasArea = Schema::getColumnListing('areas');

        foreach (self::AREAS as $nombre) {
            Area::firstOrCreate(['nombre' => $nombre], in_array('descripcion', $columnasArea) ? ['descripcion' => "Área de {$nombre}"] : []);
        }

        foreach (self::CARGOS as $nombre) {
            Cargo::firstOrCreate(['nombre' => $nombre]);
        }

        foreach (self::CATEGORIAS as $nombre => $descripcion) {
            Categoria::firstOrCreate(['nombre' => $nombre], ['descripcion' => $descripcion]);
        }

        // una oficina por area
        foreach (Area::all() as $area) {
            Oficina::firstOrCreate(['nombre' => mb_substr("Oficina de {$area->nombre}", 0, 50)], [
                'descripcion' => "Oficina del área {$area->nombre}", 'area_id' => $area->id,
            ]);
        }
    }
}

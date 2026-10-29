<?php

namespace Database\Seeders;

use App\Models\Area;
use App\Models\Cargo;
use App\Models\Categoria;
use App\Models\Oficina;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;


// areas, cargos, categorias y oficinas: unica fuente de los catalogos (se puede correr varias veces)
class CatalogosSeeder extends Seeder
{

    // catalogos REALES del Tribunal de Cuentas (areas, cargos y categorias CAT 1 a CAT 12)
    public const AREAS = [
        'Archivo', 'Auditoria', 'Comision de estudios 1', 'Comision de estudios 2', 'Computos',
        'Fiscal General 1', 'Fiscal General 2', 'Habilitación', 'Mesa de entradas', 'Municipios',
        'Personal', 'Rendición de cuentas', 'Secretaria General', 'Servicios Generales',
    ];

    public const CARGOS = [
        'Administrativo', 'Auditoria', 'Comisión de Gobierno', 'Consejero/a de Cuentas', 'Coordinador Ejecutivo',
        'Fiscal Jefe/a', 'Gabinete Técnico', 'Informático', 'Interventor', 'Oficial Mayor de Estudios',
        'Oficial Principal de Despacho', 'Operador', 'Ordenanza', 'Presidente/a', 'Relator Mayor Coordinador',
        'Sección de Fiscalización', 'Secretaria General', 'Servicio Jurídico del Estado', 'Vocero',
    ];

    public const CATEGORIAS = [
        'CAT 1' => 'Secretario General - Contador/Fiscal',
        'CAT 2' => 'Jefe de Rendición',
        'CAT 3' => 'Auditor Junior - Sin Denominación',
        'CAT 4' => 'Auditor Semijunior - Sin Denominación (sin título)',
        'CAT 5' => 'Jefe de Despacho - Auditor Junior',
        'CAT 6' => 'Operador',
        'CAT 7' => 'Jefe de Sección',
        'CAT 8' => 'Auxiliar Administrativo A - Revisor A',
        'CAT 9' => 'Auxiliar Administrativo B - Revisor B',
        'CAT 10' => 'Auxiliar Administrativo C - Revisor C',
        'CAT 11' => 'Mayordomo',
        'CAT 12' => 'Ordenanza - Chofer',
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

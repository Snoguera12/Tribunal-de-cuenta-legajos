<?php

namespace Database\Seeders;

use App\Models\Documento;
use App\Models\Legajo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

// le pone 5 documentos de prueba a cada legajo (dni, licencia, historial, titulo y uno de texto)
// los archivos deben estar en database/seeders/documentos_prueba/
class DocumentosPruebaSeeder extends Seeder
{
    private string $origenDir;

    public function run(): void
    {
        $this->origenDir = database_path('seeders/documentos_prueba');

        if (!is_dir($this->origenDir)) {
            $this->command?->error(
                "No encontré la carpeta {$this->origenDir}. ".
                "Copiá ahí los 15 archivos de ejemplo (01_dni_prueba.pdf, .jpg, .jpeg, etc.) antes de correr este seeder."
            );
            return;
        }

        $formatos = ['pdf', 'jpg', 'jpeg'];

        // nombre archivo, tipo fijo (null es al azar), descripcion
        $plantillas = [
            ['01_dni_prueba', 0, 'DNI (documento de prueba)'],
            ['02_licencia_por_enfermedad', 3, 'Licencia por enfermedad (documento de prueba)'],
            ['03_historial_laboral', 11, 'Historial / antecedentes laborales (documento de prueba)'],
            ['04_titulo_de_carrera', 1, 'Título de carrera (documento de prueba)'],
            ['05_documento_texto_libre', null, 'Documento de prueba'],
        ];

        // los otros tipos que quedan, para el doc de texto libre


        $tiposRestantes = [
            2 => 'Cursos', 4 => 'Acta de Nacimiento', 5 => 'Certificado de Escolaridad',
            6 => 'Certificado Defunción', 7 => 'Certificado de Casamiento', 8 => 'Sumario',
            9 => 'Resolución', 10 => 'Foto de Perfil', 12 => 'Otro',
        ];
        $tiposRestantesIds = array_keys($tiposRestantes);

        $legajos = Legajo::orderBy('id')->get();
        if ($legajos->isEmpty()) {
            $this->command?->error('No hay legajos cargados todavía. Corré primero PersonalSantiago206Seeder.');
            return;
        }

        Storage::disk('local')->makeDirectory('documentos');

        $seed = 0;
        $creados = 0;
        $faltantes = [];

        foreach ($legajos as $legajo) {
            foreach ($plantillas as [$base, $tipodocFijo, $descripcion]) {
                $formato = $formatos[$seed % 3];
                $origen = "{$this->origenDir}/{$base}.{$formato}";

                if (!file_exists($origen)) {
                    $faltantes[$origen] = true;
                    $seed++;
                    continue;
                }

                $tipodoc = $tipodocFijo ?? $tiposRestantesIds[$seed % count($tiposRestantesIds)];
                $descripcionFinal = $tipodocFijo === null
                    ? $descripcion . ' — cargado como "' . $tiposRestantes[$tipodoc] . '"'
                    : $descripcion;

                $nombreDestino = Str::random(40) . '.' . $formato;
                $rutaDestino = "documentos/{$nombreDestino}";

                Storage::disk('local')->put($rutaDestino, file_get_contents($origen));

                Documento::create([
                    'ruta' => $rutaDestino,
                    'descripcion' => $descripcionFinal,
                    'tipodoc' => $tipodoc,
                    'legajo_id' => $legajo->id,
                ]);

                $creados++;
                $seed++;
            }
        }

        foreach (array_keys($faltantes) as $rutaFaltante) {
            $this->command?->warn("Archivo de plantilla no encontrado (se salteó): {$rutaFaltante}");
        }

        $this->command?->info("Documentos de prueba creados: {$creados} (para {$legajos->count()} legajos).");
    }
}

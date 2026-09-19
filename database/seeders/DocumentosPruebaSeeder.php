<?php

namespace Database\Seeders;

use App\Models\Documento;
use App\Models\Legajo;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Carga, para CADA legajo existente, los 5 documentos de prueba generados
 * (DNI, Licencia por enfermedad, Historial laboral, Título de carrera y un
 * documento de texto libre), tomando para cada uno al azar UNO de los 3
 * formatos disponibles (PDF, JPG o JPEG).
 *
 * El documento "texto libre" no representa un tipo fijo del ABM: se le
 * asigna al azar uno de los tipos restantes (Acta de Nacimiento, Cursos,
 * Certificado de Escolaridad, Certificado de Defunción, Certificado de
 * Casamiento, Sumario, Resolución, Foto de Perfil u Otro), para dejar
 * cargada variedad de tipos de documento en el sistema.
 *
 * Requiere que los 15 archivos de plantilla (5 documentos x 3 formatos)
 * estén dentro de database/seeders/documentos_prueba/.
 *
 * Los archivos quedan guardados físicamente en storage/app/private/documentos/
 * (el mismo disco/carpeta que usa el formulario real de subida), así que se
 * pueden abrir y descargar desde el panel exactamente igual que un archivo
 * subido a mano.
 */
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

        // [nombre_base_del_archivo, tipodoc_fijo (null = aleatorio), descripción]
        $plantillas = [
            ['01_dni_prueba', 0, 'DNI (documento de prueba)'],
            ['02_licencia_por_enfermedad', 3, 'Licencia por enfermedad (documento de prueba)'],
            ['03_historial_laboral', 11, 'Historial / antecedentes laborales (documento de prueba)'],
            ['04_titulo_de_carrera', 1, 'Título de carrera (documento de prueba)'],
            ['05_documento_texto_libre', null, 'Documento de prueba'],
        ];

        // Tipos "restantes" del ABM (sin contar DNI, Título, Licencia y Curriculum,
        // ya usados arriba) para repartir entre el documento de texto libre.
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

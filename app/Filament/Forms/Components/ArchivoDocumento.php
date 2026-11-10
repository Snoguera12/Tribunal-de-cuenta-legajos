<?php

namespace App\Filament\Forms\Components;

use App\Models\Documento;
use App\Rules\ArchivoPdfOJpeg;
use App\Services\ArchivoSeguro;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Storage;

class ArchivoDocumento extends FileUpload
{
    public const DISCO = 'local';
    public const DIRECTORIO = 'documentos'; // sin barra final
    public const MAX_KB = 10240;            // 10 MB

    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->disk(self::DISCO)
            ->visibility('private')
            ->directory(self::DIRECTORIO)
            ->maxSize(self::MAX_KB)
            ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
            // además de la extensión, se revisa el contenido real del archivo (firma, contenido activo, antivirus)
            ->rules(['mimes:pdf,jpg,jpeg', new ArchivoPdfOJpeg(registrar: false)]) // al guardar se vuelve a comprobar, sin repetir auditoría ni antivirus
            ->validationMessages(['required' => 'Debe subir un archivo válido.'])
            // el archivo se recomprime, se cifra y se guarda con un nombre que sale de su contenido (el mismo archivo no ocupa lugar dos veces)
            ->saveUploadedFileUsing(fn ($file) => ArchivoSeguro::guardar($file, self::DIRECTORIO))
            // el archivo guardado está cifrado: para abrirlo hay que pasar por la ruta que lo descifra
            ->getUploadedFileUsing(function (string $file): ?array {
                $disco = Storage::disk(self::DISCO);

                if (! $disco->exists($file)) {
                    return null;
                }

                $documento = Documento::where('ruta', $file)->first();

                return [
                    'name' => $documento?->nombre_original ?: basename($file),
                    'size' => $disco->size($file),
                    'type' => ArchivoSeguro::tipoMime($file),
                    'url' => $documento ? (Documento::abrirDocumento($documento) ?? '') : '',
                ];
            })
            ->storeFileNamesIn('nombre_original')
            // La revisión del contenido se hace acá, cada vez que cambia el archivo, y no con la regla de Filament:
            // así un archivo rechazado se saca del formulario y el cartel de error se borra solo al reemplazarlo.
            ->afterStateUpdated(function (FileUpload $component, $livewire, $state): void {
                $ruta = $component->getStatePath();

                // los errores de cada archivo se guardan como "campo.<id>", por eso también se borra con el comodín
                $livewire->resetErrorBag([$ruta, $ruta . '.*']);

                foreach (Arr::wrap($state) as $archivo) {
                    if (! ($archivo instanceof UploadedFile)) {
                        continue; // ya estaba guardado: se revisó cuando se subió
                    }

                    $mensaje = null;

                    (new ArchivoPdfOJpeg())->validate('archivo', $archivo, function (string $texto) use (&$mensaje): void {
                        $mensaje ??= $texto;
                    });

                    if ($mensaje !== null) {
                        $component->state(null); // el archivo rechazado no queda en el formulario
                        $livewire->addError($ruta, $mensaje);

                        return;
                    }
                }
            })
            ->deletable(false)
            ->required();
    }
}
<?php

namespace App\Filament\Forms\Components;

use App\Models\Documento;
use Filament\Forms\Components\FileUpload;

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
            ->rules(['mimes:pdf,jpg,jpeg'])
            ->validationMessages(['required' => 'Debe subir un archivo válido.'])
            ->getUploadedFileNameForStorageUsing(Documento::nombreAleatorio(...))
            ->storeFileNamesIn('nombre_original')
            ->deletable(false)
            ->required();
    }
}
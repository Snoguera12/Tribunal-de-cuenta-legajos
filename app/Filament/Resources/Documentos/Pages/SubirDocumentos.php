<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Resources\Documentos\DocumentoResource;
use App\Models\Documento;
use BackedEnum;
use Filament\Forms\Components\FileUpload;
use Filament\Schemas\Schema;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Resources\Pages\Page;
use Filament\Actions\Action;
use Filament\Notifications\Notification;

class SubirDocumentos extends Page implements HasForms
{
    use InteractsWithForms;
    protected static string $resource = DocumentoResource::class;
    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-tray';
    protected string $view = 'filament.pages.subir-documentos';
    protected static string|\UnitEnum|null $navigationGroup = "Papeles";
    protected static ?int $navigationSort = 6;
    public ?array $data = [];
    public static function canAccess(array $parameters = []): bool{
        // devuelve bool: si tira abort aca se rompe el menu de los otros roles
        return auth()->user()?->isAdmin_RRHH() ?? false;
    }
    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                FileUpload::make('archivos')
                ->label('Archivos')
                ->multiple()
                ->directory('documentos') // subdirectorio dentro del disk
                ->disk('local') // o el disk que uses
                ->visibility('private')
                ->maxSize(10240)
                ->acceptedFileTypes(['application/pdf', 'image/jpeg'])
                ->rules([new \App\Rules\ArchivoPdfOJpeg()])
                ->saveUploadedFileUsing(fn ($file) => \App\Services\ArchivoSeguro::guardar($file, 'documentos'))
                ->validationMessages([
                    'mimes' => 'Solo se permiten archivos PDF, JPG o JPEG.',
                    'max' => 'El archivo no puede superar los 10 MB.',
                ])
                //->preserveFilenames()
                ->required(),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('guardar')
            ->label('Guardar')
            ->submit('guardar')
            ->formId('form'), // apunta al id del <form>
        ];
    }

    public function guardar(): void
    {
        $paths = $this->form->getState()['archivos'] ?? [];

        foreach ($paths as $path) {
            Documento::create([
                'ruta' => $path,
            ]);
        }

        $this->form->fill(); // limpia el form

        Notification::make()
            ->title('Documentos subidos correctamente')
            ->success()
            ->send();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
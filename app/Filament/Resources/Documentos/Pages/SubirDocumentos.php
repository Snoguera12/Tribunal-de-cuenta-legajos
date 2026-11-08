<?php

namespace App\Filament\Resources\Documentos\Pages;

use App\Filament\Forms\Components\ArchivoDocumento;
use App\Filament\Resources\Documentos\DocumentoResource;
use App\Models\Documento;
use BackedEnum;
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
        if(!auth()->user()->isAdmin_RRHH()){
            abort(403, 'No tienes acceso a esta función');
        }
        return true;
    }
    protected function getHeaderActions(): array
    {
        return [
            Action::make('guardar')
                ->label('Guardar')
                ->icon('heroicon-o-check')
                ->action('guardar'),
        ];
    }
    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                ArchivoDocumento::make('archivos')
                ->label('Archivos')
                ->multiple()
                ->storeFileNamesIn('nombres')
                ->panelLayout('grid')
                ->imagePreviewHeight('180')
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
        $estado = $this->form->getState();
        $paths = $estado['archivos'] ?? [];
        $nombres = $estado['nombres'] ?? [];

        foreach ($paths as $path) {
            Documento::create([
                'ruta' => $path,
                'nombre_original' => $nombres[$path] ?? basename($path),
            ]);
        }

        $this->form->fill(); // limpia el form

        Notification::make()
        ->title('Documentos subidos correctamente')
        ->success()
        ->send();

        $this->redirect(DocumentoResource::getUrl('index'), navigate: true);
    }
}
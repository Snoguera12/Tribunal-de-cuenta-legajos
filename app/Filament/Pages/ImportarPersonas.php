<?php

namespace App\Filament\Pages;

use App\Imports\PersonasImport;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Maatwebsite\Excel\Facades\Excel;

class ImportarPersonas extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.importar-personas';
    protected static ?string $navigationLabel = 'Importar Personas';
    protected static string|\UnitEnum|null $navigationGroup = 'Agentes';
    protected static ?int $navigationSort = 2;
    protected static ?string $title = 'Importar Personas desde Excel';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    protected function getFormSchema(): array
    {
        return [
            FileUpload::make('archivo')
                ->label('Archivo Excel')
                ->acceptedFileTypes(['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/vnd.ms-excel'])
                ->required(),
        ];
    }

    public function importar(): void
    {
        $data = $this->form->getState();
        $path = storage_path('app/public/' . $data['archivo']);

        Excel::import(new PersonasImport, $path);

        Notification::make()
            ->title('Personas importadas correctamente')
            ->success()
            ->send();

        $this->form->fill();
    }
}
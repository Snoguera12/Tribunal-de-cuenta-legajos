<?php

namespace App\Filament\Resources\Titulos\Pages;

use App\Filament\Resources\Titulos\TituloResource;
use App\Models\EstudioTitulo;
use App\Models\Titulo;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\EmbeddedTable;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class PendientesTitulos extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = TituloResource::class;
    protected static ?string $title = 'Títulos pendientes';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
            ->label('Volver a títulos')
            ->icon('heroicon-m-arrow-left')
            ->color('gray')
            ->url(fn () => TituloResource::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
        ->query(EstudioTitulo::query()->pendientes()->with('estudio.persona'))
        ->columns([
            TextColumn::make('titulo_otro')
            ->label('Texto escrito')
            ->searchable()
            ->sortable(),

            TextColumn::make('created_at')->label('Cargado')->dateTime('d/m/Y')->sortable(),
        ])
        ->defaultSort('titulo_otro')
        ->groups([
            Group::make('titulo_otro')
            ->label('Texto escrito')
            ->collapsible()
        ])
        ->emptyStateHeading('No hay títulos pendientes')
        ->toolbarActions([
            BulkActionGroup::make([
                BulkAction::make('asignar')
                ->label('Asignar a un título del catálogo')
                ->icon('heroicon-m-link')
                ->schema([
                    Select::make('titulo_id')
                    ->label('Título')
                    ->options(fn () => Titulo::orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->required(),
                ])
                ->action(function (Collection $records, array $data) {
                    EstudioTitulo::asignarATitulo($records, (int) $data['titulo_id']);
                    Notification::make()->title('Registros asignados')->success()->send();
                })
                ->deselectRecordsAfterCompletion(),

                BulkAction::make('crear_y_asignar')
                ->label('Crear título nuevo y asignar')
                ->icon('heroicon-m-plus-circle')
                ->deselectRecordsAfterCompletion()
                ->schema([
                    TextInput::make('nombre')->label('Nombre del título nuevo')->required()->maxLength(100),
                ])
                ->action(function (Collection $records, array $data) {
                    $titulo = Titulo::firstOrCreate(['nombre' => Str::squish($data['nombre'])]);
                    EstudioTitulo::asignarATitulo($records, $titulo->id);
                    Notification::make()->title('Título creado y asignado')->success()->send();
                }),
            ]),
        ]);
    }
}
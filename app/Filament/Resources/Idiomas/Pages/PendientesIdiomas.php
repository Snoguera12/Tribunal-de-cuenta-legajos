<?php

namespace App\Filament\Resources\Idiomas\Pages;

use App\Filament\Resources\Idiomas\IdiomaResource;
use App\Models\Idioma;
use App\Models\PersonaIdioma;
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

class PendientesIdiomas extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = IdiomaResource::class;
    protected static ?string $title = 'Idiomas pendientes';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
                ->label('Volver a idiomas')
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(fn () => IdiomaResource::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(PersonaIdioma::query()->pendientes()->with('persona'))
            ->columns([
                TextColumn::make('idioma_otro')
                    ->label('Texto escrito')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('nivel')
                    ->label('Nivel')
                    ->badge(),

                TextColumn::make('created_at')->label('Cargado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('idioma_otro')
            ->groups([
                Group::make('idioma_otro')
                    ->label('Texto escrito')
                    ->collapsible(),
            ])
            ->emptyStateHeading('No hay idiomas pendientes')
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('asignar')
                        ->label('Asignar a un idioma del catálogo')
                        ->icon('heroicon-m-link')
                        ->schema([
                            Select::make('idioma_id')
                                ->label('Idioma')
                                ->options(fn () => Idioma::orderBy('nombre')->pluck('nombre', 'id'))
                                ->searchable()
                                ->required(),
                        ])
                        ->action(function (Collection $records, array $data) {
                            PersonaIdioma::asignarAIdioma($records, (int) $data['idioma_id']);
                            Notification::make()->title('Registros asignados')->success()->send();
                        })
                        ->deselectRecordsAfterCompletion(),

                    BulkAction::make('crear_y_asignar')
                        ->label('Crear idioma nuevo y asignar')
                        ->icon('heroicon-m-plus-circle')
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            TextInput::make('nombre')->label('Nombre del idioma nuevo')->required()->maxLength(100),
                        ])
                        ->action(function (Collection $records, array $data) {
                            $idioma = Idioma::firstOrCreate(['nombre' => Str::squish($data['nombre'])]);
                            PersonaIdioma::asignarAIdioma($records, $idioma->id);
                            Notification::make()->title('Idioma creado y asignado')->success()->send();
                        }),
                ]),
            ]);
    }
}
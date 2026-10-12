<?php

namespace App\Filament\Resources\Institucions\Pages;

use App\Filament\Resources\Institucions\InstitucionResource;
use App\Models\Curso;
use App\Models\Estudio;
use App\Models\Institucion;
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
use Livewire\Attributes\Url;
use App\Enums\NivelEstudioEnum; 

class PendientesInstitucion extends Page implements HasTable
{
    use InteractsWithTable;

    protected static string $resource = InstitucionResource::class;
    protected static ?string $title = 'Instituciones pendientes';

    #[Url]
    public string $origen = 'estudios';

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('volver')
            ->label('Volver a instituciones')
            ->icon('heroicon-m-arrow-left')
            ->color('gray')
            ->url(fn () => InstitucionResource::getUrl()),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema->components([EmbeddedTable::make()]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(fn () => Institucion::consultaPendientes())
            ->columns([
                TextColumn::make('origen')
                ->label('Proviene de')
                ->badge()
                ->color(fn (string $state) => $state === 'Estudio' ? 'info' : 'success')
                ->sortable(),

                TextColumn::make('institucion_otro')
                ->label('Texto escrito')
                ->searchable()
                ->sortable(),

                TextColumn::make('nivel_estudio')
                ->label('Nivel')
                ->placeholder('-')
                ->formatStateUsing(fn ($state) => filled($state) ? NivelEstudioEnum::tryFrom((int) $state)?->getLabel() : null),
                TextColumn::make('curso')->label('Curso')->placeholder('-')->searchable(),
                TextColumn::make('created_at')->label('Cargado')->dateTime('d/m/Y')->sortable(),
            ])
            ->defaultSort('institucion_otro')
            ->groups([Group::make('institucion_otro')->label('Texto escrito')->collapsible()])
            ->emptyStateHeading('No hay instituciones pendientes')
            ->toolbarActions([
            BulkActionGroup::make([
                BulkAction::make('asignar')
                    ->label('Asignar a una institución del catálogo')
                    ->icon('heroicon-m-link')
                    ->schema([
                        Select::make('institucion_id')
                        ->label('Institución')
                        ->options(fn () => Institucion::orderBy('nombre')->pluck('nombre', 'id'))
                        ->searchable()
                        ->required(),
                    ])
                    ->action(function (Collection $records, array $data) {
                        Institucion::asignarPendientes($records, (int) $data['institucion_id']);
                        Notification::make()->title('Registros asignados')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('crear_y_asignar')
                    ->label('Crear institución nueva y asignar')
                    ->icon('heroicon-m-plus-circle')
                    ->schema([
                        TextInput::make('nombre')->label('Nombre de la institución nueva')->required()->maxLength(300),
                    ])
                    ->action(function (Collection $records, array $data) {
                        $institucion = Institucion::firstOrCreate(['nombre' => Str::squish($data['nombre'])]);
                        Institucion::asignarPendientes($records, $institucion->id);
                        Notification::make()->title('Institución creada y asignada')->success()->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ]),
        ]);
    }
}
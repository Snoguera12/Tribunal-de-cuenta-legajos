<?php

namespace App\Filament\Resources\Personas\Schemas;

use App\Enums\UserEnum;
use App\Models\AntecedenteLaboral;
use App\Models\Curso;
use App\Models\Estudio;
use App\Models\Familiar;
use App\Models\PersonaIdioma;
use App\Models\Legajo;
use App\Models\Persona;
use App\Models\User;
use App\Filament\Resources\Personas\PersonaResource;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
        ->components([
            Tabs::make('Tabs Base')
            ->columns(2)
            ->columnSpanFull()
            ->persistTabInQueryString()
            ->tabs([
                Tab::make('Tab Persona')
                ->label('Persona')
                ->id('persona')
                ->key('persona')
                ->icon('heroicon-m-identification')
                ->columns(4)
                ->columnSpanFull()
                ->schema(Persona::getFormSchema()),

                Tab::make('Tab Legajos/Documentos')
                ->icon('heroicon-m-folder-open')
                ->id('legajo')
                ->key('legajo')
                ->label('Legajos')
                ->icon('heroicon-m-folder-open')
                ->schema(Legajo::getRepeaterSchema()),

                Tab::make('Tab Estudios/Títulos')
                ->label("Estudios/Títulos")
                ->id('estudio')
                ->key('estudio')
                ->icon('heroicon-m-academic-cap')
                ->columnSpanFull()
                ->schema(Estudio::getRepeaterSchema()),

                Tab::make('Tab Cursos')
                ->label('Cursos')
                ->id('curso')
                ->key('curso')
                ->icon('heroicon-m-book-open')
                ->columnSpanFull()
                ->schema(Curso::getRepeaterSchema()),

                Tab::make('Tab Antecedentes Laborales')
                ->label('Antecedentes Laborales')
                ->id('antecedenteslaborales')
                ->key('antecedenteslaborales')
                ->icon('heroicon-m-briefcase')
                ->columnSpanFull()
                ->schema(AntecedenteLaboral::getRepeaterSchema()),
                
                Tab::make('Tab Familiares')
                ->label('Familiares')
                ->id('familiar')
                ->key('familiar')
                ->icon('heroicon-m-users')
                ->schema(Familiar::getRepeaterSchema()),

                Tab::make('Tab Idiomas')
                ->label('Idiomas')
                ->id('idioma')
                ->key('idioma')
                ->icon('heroicon-m-language')
                ->schema(PersonaIdioma::getRepeaterSchema()),

                Tab::make('Tab Usuario')
                ->label('Usuario')
                ->icon('heroicon-m-user-circle')
                ->id('usuario')
                ->key('usuario')
                ->columnSpanFull()
                ->visible(fn () => auth()->user()->isAdmin_RRHH())
                ->schema([
                    Toggle::make('crear_usuario')
                    ->label('Crear usuario para esta persona')
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (?Persona $record, Get $get) => ! $record?->usuario()->exists()
                        && blank($get('usuario_existente')))
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        if (!$state) {
                            return;
                        }

                        if (blank($get('usuario.email'))) {
                            $set('usuario.email', $get('email'));
                        }

                        if (blank($get('usuario.name'))) {
                            $set('usuario.name', trim($get('nombre') . ' ' . $get('apellido')));
                        }
                    }),

                    Select::make('usuario_existente')
                    ->label('Adjuntar un usuario existente')
                    ->placeholder('Elegí un usuario sin persona asignada')
                    ->helperText('Se adjunta al presionar Guardar.')
                    ->searchable()
                    ->live()
                    ->dehydrated(false)
                    ->options(function () {
                            $roles = [
                                'empleado' => UserEnum::Empleado,
                                'funcionario' => UserEnum::Funcionario,
                                'rrhh' => UserEnum::RRHH,
                                'administrador' => UserEnum::Administrador,
                            ];

                            return User::whereNull('persona_id')
                                ->orderBy('name')
                                ->get()
                                ->mapWithKeys(fn (User $u) => [
                                    $u->id => "{$u->name} - {$u->email} (" . (($roles[$u->rol] ?? null)?->getLabel() ?? ucfirst((string) $u->rol)) . ')',
                                ])
                                ->all();
                        })
                        ->visible(fn (?Persona $record, Get $get) => ! $record?->usuario()->exists()
                        && ! $get('crear_usuario'))
                        ->saveRelationshipsUsing(function ($state, Persona $record, $livewire): void {
                        if (blank($state)) {
                            return;
                        }

                        abort_unless(auth()->user()?->isAdmin_RRHH(), 403);
                        abort_if($record->usuario()->exists(), 409, 'Esta persona ya tiene un usuario.');

                        $usuario = User::whereKey($state)
                            ->whereNull('persona_id')
                            ->firstOr(fn () => abort(409, 'El usuario ya no está disponible. Recargá la página.'));

                        $usuario->persona_id = $record->getKey();
                        $usuario->save();

                        // Recargar la página para que el formulario muestre los datos del usuario adjuntado.
                        $record->unsetRelation('usuario');
                        $livewire->redirect(PersonaResource::getUrl('edit', ['record' => $record]));
                    }),

                    Toggle::make('desadjuntar_usuario')
                    ->label('Desadjuntar el usuario de esta persona')
                    ->helperText('El usuario no se borra: queda sin persona y se puede volver a adjuntar. Se aplica al presionar Guardar.')
                    ->live()
                    ->dehydrated(false)
                    ->visible(fn (?Persona $record) => (bool) $record?->usuario()->exists())
                    ->saveRelationshipsUsing(function ($state, Persona $record, $livewire): void {
                        if (! $state) {
                            return;
                        }

                        abort_unless(auth()->user()?->isAdmin_RRHH(), 403);

                        $usuario = $record->usuario()->first();

                        if (! $usuario) {
                            return;
                        }

                        abort_if($usuario->is(auth()->user()), 403, 'No podés desadjuntar tu propio usuario.');

                        $usuario->persona_id = null;
                        $usuario->save();

                        $record->unsetRelation('usuario');
                        $livewire->redirect(PersonaResource::getUrl('edit', ['record' => $record]));
                    }),

                    Group::make()
                    ->columnSpanFull()
                    ->relationship(
                        'usuario',
                        condition: fn (?array $state) => filled($state['email'] ?? null),
                    )
                    ->columns(2)
                    ->visible(fn (Get $get, ?Persona $record) =>
                        ($record?->usuario()->exists() || $get('crear_usuario'))
                        && ! $get('desadjuntar_usuario'))
                    ->schema(User::getFormSchema(false)),
                ]),

            ]),
        ]);
    }
}


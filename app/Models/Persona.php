<?php

namespace App\Models;

use App\Models\Scopes\AlcanceEmpleado;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use App\Models\Concerns\NoSeBorra;
use App\Models\Concerns\RegistraAuditoria;
use App\Enums\EstadoCivilEnum;
use App\Enums\GeneroEnum;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy([AlcanceEmpleado::class])]
class Persona extends Model
{
    use NoSeBorra, RegistraAuditoria;

    protected $casts = [
        'genero' => GeneroEnum::class,
        'estado_civil' => EstadoCivilEnum::class,
    ];
    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'cuil',
        'email',
        'genero',
        'estado_civil',
        'fecha_de_nacimiento',
        'domicilio',
        'telefono',
        'telefono_emergencia',
        'borrar_logico',
    ];
    
    public function legajos() { return $this->hasMany(Legajo::class)->where('estado', 1); }
    public function estudios() { return $this->hasMany(Estudio::class); }
    public function estudiosPrincipal() { return $this->hasMany(Estudio::class)->mayorNivel()->with('estudioTitulos.titulo'); }
    public function estudiosAnteriores() { return $this->hasMany(Estudio::class)->otrosNiveles()->with('estudioTitulos.titulo');}
    public function familiares() { return $this->hasMany(Familiar::class); }
    public function cursos() { return $this->hasMany(Curso::class); }
    public function idiomas() { return $this->hasMany(PersonaIdioma::class); }
    public function antecedentesLaborales() { return $this->hasMany(AntecedenteLaboral::class); }
    public function usuario() { return $this->hasOne(User::class); }
    public static function getFormSchema(): array{
        $resultado = [
            TextInput::make('nombre')->label('Nombre')
            ->required()
            ->autocomplete(false)
            ->maxLength(100)
            ->validationMessages([
                "required" => "Requiere introducir el Nombre.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, introducir el Nombre.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            TextInput::make('apellido')
            ->label('Apellido')
            ->required()
            ->autocomplete(false)
            ->maxLength(100)
            ->validationMessages([
                "required" => "Requiere introducir el Apellido.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, introducir el Apellido.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            TextInput::make('dni')->label("DNI")
            ->unique(ignoreRecord: true) // Evita errores al editar el mismo registro
            ->required()
            ->maxLength(9)
            ->autocomplete(false)
            ->live(onBlur: true)
            ->rules(['required', 'regex:/^[0-9]{7,9}$/'])
            ->validationMessages([
                "required" => "Requiere introducir el DNI.",
                "unique" => "Ya se registró el DNI.",
                "regex" => "El DNI debe contener entre 7 y 9 dígitos.",
            ])
            ->extraInputAttributes([
                'type' => 'text',
                'inputmode' => 'numeric',
                'oninvalid' => "this.setCustomValidity('Por favor, introducir el DNI.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            TextInput::make('cuil')->label("CUIL")
            ->required()
            ->unique(ignoreRecord: true)
            ->autocomplete(false)
            ->maxLength(12)
            ->rules(['required', 'regex:/^[0-9]{10,12}$/'])
            ->validationMessages([
                "required" => "Requiere introducir el CUIL.",
                "unique" => "Ya se registró el CUIL.",
                "regex" => "El CUIL debe contener entre 10 y 12 dígitos, sin guiones.",
            ])
            ->extraInputAttributes([
                'type' => 'text',
                'inputmode' => 'numeric',
            ]),

            TextInput::make('email')->label('Correo Electrónico')
            ->email()
            ->unique(ignoreRecord: true)
            ->autocomplete(false)
            ->maxLength(100)
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, escribir correctamente el correo electrónico.')",
                'oninput' => "this.setCustomValidity('')",
            ])
            ->validationMessages([
                'unique' => 'Ya existe una persona registrada con ese correo.',
            ]),
            
            Select::make('estado_civil')->label('Estado Civil')
            ->options(EstadoCivilEnum::class)
            ->required()
            ->validationMessages([
                "required" => "Requiere selecionar el estado civil.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, selecione el estado civil.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            Select::make("genero")->label("Género")
            ->options(GeneroEnum::class)
            ->required()
            ->validationMessages([
                "required" => "Requiere seleccionar el género.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, seleccione el género.')",
                'oninput' => "this.setCustomValidity('')",
                'onchange' => "this.setCustomValidity('')",
            ]),
            
            DatePicker::make('fecha_de_nacimiento')->label('Fecha de Nacimiento')
            ->maxDate(now()->subYears(18)->toDateString()) // Máximo hace 18 años
            ->rules(['date', 'before_or_equal:' . now()->subYears(18)->toDateString()])
            ->helperText('La persona tiene que ser mayor de edad.')
            ->required()
            ->validationMessages([
                "required" => "Requiere introducir la Fecha de nacimiento.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, ingrese la fecha de nacimiento.')",
                'oninput' => "this.setCustomValidity('')",
            ]),

            TextInput::make('domicilio')
            ->label('Domicilio')
            ->maxLength(250)
            ->autocomplete(false),

            TextInput::make('telefono')
            ->label('Teléfono')->tel()
            ->autocomplete(false)
            ->maxLength(20)
            ->rules(['regex:/^[0-9+\-\s]{6,20}$/']),

            TextInput::make('telefono_emergencia')
            ->label('Teléfono de emergencia')->tel()
            ->autocomplete(false)
            ->maxLength(20)
            ->rules(['regex:/^[0-9+\-\s]{6,20}$/']),
        ];
        return $resultado;
    }

    public static function getOutSchema(string $mode_out) : array{
        $resultado = match ($mode_out) {
            'table' => [
                TextColumn::make('nombre')
                ->label('Nombre')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('apellido')
                ->label('Apellido')
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('dni')
                ->label("DNI")
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('cuil')
                ->label("CUIL")
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email')
                ->label('Correo Electrónico')
                ->searchable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('genero')
                ->label('Género')
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('estado_civil')
                ->label('Estado Civil')
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('fecha_de_nacimiento')
                ->label('Nacimiento')
                ->date('d/m/Y')
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('domicilio')
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('telefono')
                ->label("Teléfono")
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('telefono_emergencia')
                ->label("Teléfono de emergencia")
                ->searchable()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
            ],
            'list' => [
                TextEntry::make('nombre')
                ->label('Nombre')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('apellido')
                ->label('Apellido')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('dni')
                ->label('DNI')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('cuil')
                ->label('CUIL')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('email')
                ->label('Correo Electrónico')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('genero')
                ->label('Género')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('estado_civil')
                ->label('Estado Civil')
                ->placeholder('Sin legajo adjuntado'),

                TextEntry::make('fecha_de_nacimiento')
                ->label('Fecha de Nacimiento')
                ->placeholder('Sin legajo adjuntado')
                ->date('d/m/Y'),

                TextEntry::make('domicilio')
                ->label('Domicilio')
                ->placeholder('-'),

                TextEntry::make('telefono')
                ->label('Teléfono')
                ->placeholder('-'),

                TextEntry::make('telefono_emergencia')
                ->label('Teléfono de Emergencia')
                ->placeholder('-'),
            ],
        };

        return $resultado;
    }
}

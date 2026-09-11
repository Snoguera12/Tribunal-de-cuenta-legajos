<?php

namespace App\Models;

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
use Illuminate\Support\Collection;

class Persona extends Model
{
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
    
    public function legajos()
    {
        return $this->hasMany(Legajo::class)->where('estado', 1);
    }
    public function estudios()
    {
        return $this->hasMany(Estudio::class);
    }
    public function familiares()
    {
        return $this->hasMany(Familiar::class);
    }
    public function cursos()
    {
        return $this->hasMany(Curso::class);
    }
    public function idiomas()
    {
        return $this->hasMany(Idioma::class);
    }
    public function antecedentesLaborales()
    {
        return $this->hasMany(AntecedenteLaboral::class);
    }
    public function estudioPrioritario()
    {
        return $this->hasOne(Estudio::class)
            ->orderByRaw("CASE 
                WHEN nivel_estudio = 'Universitario' THEN 1
                WHEN nivel_estudio = 'Terciario' THEN 2
                WHEN nivel_estudio = 'Secundario' THEN 3
                WHEN nivel_estudio = 'Primario' THEN 4
                ELSE 5 
            END")->latestOfMany(); // Garantiza que devuelva un solo registro compatible con Section::relationship
    }
    public function usuario()
    {
        return $this->hasOne(User::class);
    }

    public static function getPersonas(): array
    {
        return self::query()
        ->get(['id', 'nombre', 'apellido', 'dni'])
        ->mapWithKeys(fn ($item) => [
            $item->id => "{$item->nombre} {$item->apellido} (DNI: {$item->dni})"
        ])
        ->toArray();
    }
    public static function getFormSchema(): array{
        $resultado = [
            TextInput::make('nombre')->label('Nombre')
            ->required()
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
            ->maxLength(8)
            ->live(onBlur: true)
            ->rules(['required', 'regex:/^[0-9]{7,8}$/'])
            ->validationMessages([
                "required" => "Requiere introducir el DNI.",
                "unique" => "Ya se registró el DNI.",
                "regex" => "El DNI debe contener entre 7 y 8 dígitos.",
            ])
            ->extraInputAttributes([
                'type' => 'text',
                'inputmode' => 'numeric',
                'oninvalid' => "this.setCustomValidity('Por favor, introducir el DNI.')",
                'oninput' => "this.setCustomValidity('')",
            ])
            ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null)
            // Se ejecuta al perder el foco si el CUIL está vacío
            ->afterStateUpdated(function (string|null $state, Set $set, Get $get) {
                if (blank($state) || filled($get('cuil'))) {
                    return;
                }

                // El DNI físico se rellena con ceros a la izquierda hasta tener 8 dígitos para armar el CUIL estándar
                $dniPad = str_pad(preg_replace('/\D/', '', $state), 8, '0', STR_PAD_LEFT);

                // Prefijo genérico 20 y sufijo 2 (El usuario podrá corregirlo si es mujer/empresa)
                $prefijo = '20';
                $sufijo = '2';

                $set('cuil', $prefijo . $dniPad . $sufijo);
            }),

            TextInput::make('cuil')->label("CUIL")
            ->required()
            ->unique(ignoreRecord: true)
            ->maxLength(11)
            // Pasamos un Closure a rules() para que Filament nos inyecte la instancia de Get correctamente
            ->rules(fn (Get $get): array => [
                'regex:/^\d{11}$/', // Formato numérico puro de 11 dígitos
                function (string $attribute, $value, $fail) use ($get) {
                    $dni = preg_replace('/\D/', '', (string) $get('dni'));
                    $cuilNumericoPuro = preg_replace('/\D/', '', (string) $value);

                    if (blank($dni) || strlen($cuilNumericoPuro) !== 11) {
                        return;
                    }

                    // El DNI dentro del CUIL siempre ocupa 8 dígitos (de la posición 2 a la 9)
                    $dniEnCuil = substr($cuilNumericoPuro, 2, 8);
                    $dniConCeros = str_pad($dni, 8, '0', STR_PAD_LEFT);

                    if ($dniEnCuil !== $dniConCeros) {
                        $fail("El número de documento intermedio ({$dniEnCuil}) no coincide con el DNI ingresado ({$dni}).");
                    }
                },
            ])
            ->validationMessages([
                "required" => "Requiere introducir el CUIL.",
                "unique" => "Ya se registró el CUIL.",
                "regex" => "El CUIL debe contener exactamente 11 números sin guiones.",
            ])
            ->extraInputAttributes([
                'type' => 'text',
                'inputmode' => 'numeric',
            ])
            ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null),

            TextInput::make('email')->label('Correo Electrónico')
            ->email()
            ->unique(ignoreRecord: true)
            ->maxLength(255)
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
                "required" => "Requiere selecionar el género.",
            ])
            ->extraInputAttributes([
                'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                'oninput' => "this.setCustoEstadoCivilEnum::classmValidity('')",
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
            ->label('Domicilio'),

            TextInput::make('telefono')
            ->label('Teléfono')->tel()
            ->rules(['regex:/^[0-9+\-\s]{6,20}$/']),
            TextInput::make('telefono_emergencia')
            ->label('Teléfono de emergencia')->tel()
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
                ->label('Nombre'),

                TextEntry::make('apellido')
                ->label('Apellido'),

                TextEntry::make('dni')
                ->label('DNI'),

                TextEntry::make('cuil')
                ->label('CUIL'),

                TextEntry::make('email')
                ->label('Correo Electrónico')
                ->placeholder('-'),

                TextEntry::make('genero')
                ->label('Género'),

                TextEntry::make('estado_civil')
                ->label('Estado Civil'),

                TextEntry::make('fecha_de_nacimiento')
                ->label('Fecha de Nacimiento')
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

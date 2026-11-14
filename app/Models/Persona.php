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
            ])
            ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null)
            // Se ejecuta al perder el foco si el CUIL está vacío
            ->afterStateUpdated(function (string|null $state, Set $set, Get $get) {
                if (blank($state) || filled($get('cuil'))) {
                    return;
                }

                if ($cuil = Persona::generarCuil($state)) {
                    $set('cuil', $cuil);
                }
            }),

            TextInput::make('cuil')->label("CUIL")
            ->required()
            ->unique(ignoreRecord: true)
            ->autocomplete(false)
            ->maxLength(12)
            // Pasamos un Closure a rules() para que Filament nos inyecte la instancia de Get correctamente
            ->rules(fn (Get $get): array => [
                function (string $attribute, $value, $fail) use ($get) {
                    if ($mensaje = Persona::errorCuil((string) $value, (string) $get('dni'))) {
                        $fail($mensaje);
                    }
                },
            ])
            ->validationMessages([
                "required" => "Requiere introducir el CUIL.",
                "unique" => "Ya se registró el CUIL.",
                "regex" => "El CUIL debe contener exactamente 12 números sin guiones.",
            ])
            ->extraInputAttributes([
                'type' => 'text',
                'inputmode' => 'numeric',
            ])
            ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null),

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

        // ── CUIL: prefijo (2 dígitos) + documento + verificador (el último). El estándar tiene 11 dígitos; se aceptan de 10 a 12 ──

    // CUIL propuesto a partir del DNI. Devuelve null si no se puede armar (se carga a mano)
    private static function generarCuil(?string $dni, string $prefijo = '20'): ?string
    {
        $dni = preg_replace('/\D/', '', (string) $dni);

        if (strlen($dni) < 7 || strlen($dni) > 8) {
            return null;
        }

        $diez = $prefijo . str_pad($dni, 8, '0', STR_PAD_LEFT);
        $verificador = self::verificadorCuil($diez);

        // caso especial de la fórmula: el prefijo pasa a 23 y el verificador es 9 (hombre) o 4 (mujer)
        if ($verificador === 10) {
            $diez = '23' . substr($diez, 2);
            $verificador = $prefijo === '27' ? 4 : 9;
        }

        return $diez . $verificador;
    }


    // texto del error, o null si el CUIL está bien
    private static function errorCuil(?string $cuil, ?string $dni): ?string
    {
        $cuil = preg_replace('/\D/', '', (string) $cuil);
        $dni = preg_replace('/\D/', '', (string) $dni);
        
        if (substr($cuil, 0, 2) === '00') {
            return 'El prefijo del CUIL no puede ser 00.';
        }

        if (! in_array(strlen($cuil), [10, 11, 12], true)) {
            return 'El CUIL debe tener entre 10 y 12 números, sin guiones.';
        }

        if ($dni !== '') {
            // entre el prefijo (2 dígitos) y el verificador (el último) va el documento
            $enCuil = substr($cuil, 2, -1);

            if ($enCuil !== str_pad($dni, strlen($enCuil), '0', STR_PAD_LEFT)) {
                return "El documento dentro del CUIL ({$enCuil}) no coincide con el DNI ingresado ({$dni}).";
            }
        }

        // el de 10 se completa con el cero que falta; el de 12 no tiene fórmula, solo se controla la estructura
        $estandar = strlen($cuil) === 10 ? substr($cuil, 0, 2) . '0' . substr($cuil, 2) : $cuil;

        if (strlen($estandar) === 11 && (int) $estandar[10] !== self::verificadorCuil(substr($estandar, 0, 10))) {
            return 'El dígito verificador del CUIL no es válido.';
        }

        return null;
    }


    // dígito verificador de los 10 primeros dígitos (10 = la fórmula no da un CUIL válido con ese prefijo)
    private static function verificadorCuil(string $diez): int
    {
        $suma = 0;

        foreach ([5, 4, 3, 2, 7, 6, 5, 4, 3, 2] as $i => $peso) {
            $suma += (int) $diez[$i] * $peso;
        }

        $resto = $suma % 11;

        return $resto === 0 ? 0 : 11 - $resto;
    }
}

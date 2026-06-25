<?php

namespace App\Filament\Resources\Personas\Schemas;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;

class PersonaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nombre')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Nombre.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el Nombre.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('apellido')
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere introducir el Apellido.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el Apellido.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('dni')
                    ->label("DNI")
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

                TextInput::make('cuil')
                    ->label("CUIL")
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


                /*TextInput::make('dni')
                    ->label("DNI")
                    ->unique()
                    ->maxLength(9)
                    ->rules(['required','regex:/^[0-9]{1,9}$/',])
                    ->required()
                    ->live(onBlur: true) 
                    // Ejecuta la función lógica cada vez que el DNI cambia
                    ->afterStateUpdated(function (string|null $state, Set $set, Get $get) {
                        // Si el DNI está vacío o ya hay un CUIL cargado manualmente, no hacemos nada
                        if (blank($state) || filled($get('cuil'))) {
                            return;
                        }

                        // Definimos prefijo y sufijo por defecto (ej: 20 y 2)
                        $prefijo = 20;
                        $sufijo = 2;

                        // Concatenamos para armar el CUIL automático: 20 + DNI + 2
                        $cuilAutomatico = $prefijo . $state . $sufijo;
                        // Asignamos el valor al campo CUIL
                        $set('cuil', $cuilAutomatico);
                    })
                    ->validationMessages([
                    "required" => "Requiere introducir el DNI.",
                    "unique" => "Ya se registró el DNI.",
                    "regex" => "El DNI debe contener 8 dígitos.",
                    ])
                    ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null)
                    ->validationMessages([
                        "required" => "Requiere introducir el DNI.",
                        "unique" => "Ya se registró el DNI."
                    ])
                    ->extraInputAttributes([
                    'type' => 'text',
                    'inputmode' => 'numeric',
                    'maxlength' => 9,
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, introducir el DNI.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                TextInput::make('cuil')
                    ->label("CUIL")
                    ->required()
                    ->unique()
                    ->maxLength(12)
                    ->rules(function (callable $get) {
                        return [
                            'regex:/^\d{2}\d{9}\d$/',
                            function (string $attribute, $value, $fail) use ($get) {
                                $dni = preg_replace('/[^0-9]/', '', (string) $get('dni'));
                                $cuilNumericoPuro = preg_replace('/[^0-9]/', '', (string) $value);


                                if ($dni && strlen($cuilNumericoPuro) === 11) {
                                    $dniEnCuil = substr($cuilNumericoPuro, 2, 9);


                                    if ($dniEnCuil !== $dni) {
                                        $fail("El número de documento intermedio ({$dniEnCuil}) no coincide con el DNI ingresado ({$dni}).");
                                    
                                
                                    }
                                    if ($dniEnCuil !== $dni) {
                                        $fail("El número de documento intermedio ({$dniEnCuil}) no coincide con el DNI ingresado ({$dni}).");
                                    }
                                }
                            },
                        ];
                    })
                    ->validationMessages([
                        "required" => "Requiere introducir el CUIL.",
                        "unique" => "Ya se registró el CUIL."
                    ])
                    ->dehydrateStateUsing(fn (string|null $state) => $state ? (int) preg_replace('/\D/', '', $state) : null)
                    ->validationMessages([
                        "required" => "Requiere introducir el CUIL.",
                        "unique" => "Ya se registró el CUIL.",
                        //"regex" => "El formato del CUIL debe ser 00-00000000-0.",
                    ]),*/
                TextInput::make('email')
                    ->label('Correo Electróico')
                    ->email()
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, escribir correctamente el correo electrónico.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make('estado_civil')
                    ->label('Estado Civil')
                    ->options([
                        0 => 'Soltero/a',
                        1 => 'Casado/a',
                        2 => 'Viúdo/a',
                    ])
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere selecionar el estado civil.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                Select::make("genero")
                    ->label("Género")
                    ->options([
                        0 => "Femenino",
                        1 => "Masculino",
                        2 => 'Otro',
                    ])
                    ->required()
                    ->validationMessages([
                        "required" => "Requiere selecionar el género.",
                    ])
                    ->extraInputAttributes([
                        'oninvalid' => "this.setCustomValidity('Por favor, selecione el género.')",
                        'oninput' => "this.setCustomValidity('')",
                    ]),
                DatePicker::make('fecha_de_nacimiento')
                ->label('Fecha de Nacimiento')
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
                TextInput::make('domicilio'),
                TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel(),
                TextInput::make('telefono_emergencia')
                    ->label('Teléfono de emergencia')
                    ->tel(),
            ]);
    }
}

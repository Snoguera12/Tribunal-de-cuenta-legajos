<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Hash;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'persona_id',
    ];
    public function persona(){
        return $this->belongsTo(Persona::class, 'persona_id');
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function isEmpleado(){
        return $this->rol == 'empleado';
    }
    public function isFuncionario(){
        return $this->rol == 'funcionario';
    }
    public function isRRHH(){
        return $this->rol == 'rrhh';
    }
    public function isAdmin(){
        return $this->rol == 'administrador';
    }
    public function isAdmin_RRHH(): bool
    {
        return $this->isAdmin() || $this->isRRHH();
    }
    public function isStaffRoles(): bool
    {
        return $this->isAdmin() || $this->isRRHH() || $this->isFuncionario();
    }
    public static function getFormSchema(bool $visible_persona_id): array
    {
        return [
            TextInput::make('name')->label('Nombre de Usuario')
                ->required(),
                
            TextInput::make('email')->label('Correo Electrónico')
            ->email()
            ->default(function () {
                // Captura el 'persona_id' enviado desde la URL
                $personaId = request()->query('persona_id');
                
                if ($personaId) {
                    // Busca la persona y retorna su correo
                    return Persona::find($personaId)?->email;
                }
                
                return null;
            })
            ->required(),

            TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->required(fn (string $context): bool => $context === 'create')
            // Si el campo está vacío al guardar, no lo incluye en el Query de actualización
            ->dehydrated(fn (?string $state) => filled($state))
            // Encripta la contraseña automáticamente antes de guardarla (si se modificó)
            ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
            ->helperText(function (string $context) {
                if ($context === 'edit') {
                    return 'Deje este campo en blanco si no desea cambiar la contraseña.';
                }
                return null; // No muestra nada al crear
            }),

            Select::make('rol')->label('Rol del Usuario')
            ->options([
                'empleado' => 'Empleado',
                'funcionario' => 'Funcionario',
                'rrhh' => 'RRHH (Recursos Humanos)',
            ])
            ->required(),

            Select::make("persona_id")->label("Persona")
            ->visible($visible_persona_id)
            ->searchable()
            ->nullable()
            ->default(fn () => request()->query('persona_id'))
            ->placeholder("Ninguna persona")
            ->options(Persona::selectRaw("id, nombre || ' ' || apellido || ' (DNI: ' || dni || ')' AS nombre_completo")->pluck('nombre_completo', 'id')),
        ];
    }

    public static function getOutputSchema(string $mode_output): array{
        $resultado = match($mode_output){
            "table" => [
                TextColumn::make('persona.nombre')
                    ->label("Nombre")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('persona.apellido')
                    ->label("Apellido")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('persona.dni')
                    ->label("DNI")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label("Nombre de Usuario")
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email')
                    ->label('Correo Electrónico')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email_verified_at')
                    ->label('Correo Verificado')
                    ->dateTime('d/m/Y H:i:s')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('rol')
                    ->label('Rol')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'empleado' => 'Empleado',
                        'funcionario' => 'Funcionario',
                        'rrhh' => 'RRHH (Recursos Humanos)',
                        'administrador' => 'Administrador',
                        default => 'Desconocido',
                    })
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('created_at')
                    ->dateTime('d/m/Y H:i:s')
                    ->label('Fecha de Creación')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                
                TextColumn::make('updated_at')
                    ->label('Fecha de Actualización')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ],
            'folist' => [
                TextEntry::make('name')->label('Nombre de Usuario')->placeholder('-'),
                TextEntry::make('email')->label('Correo Electrónico')->placeholder('-'),
                TextEntry::make('rol')->label('Rol del Usuario')->placeholder('-')
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    'empleado' => 'Empleado',
                    'funcionario' => 'Funcionario',
                    'rrhh' => 'RRHH (Recursos Humanos)',
                    'administrador' => 'Administrador',
                    default => 'Desconocido',
                }),
            ],
            default => [],
        };
        
        return $resultado;
    }
}


<?php

namespace App\Models;


use Filament\Schemas\Components\Utilities\Get;
use Hash;
use App\Models\Helpers\UserHelper;
use App\Models\Helpers\PersonaHelper;
use Database\Factories\UserFactory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
    public function persona() : HasOne{
        return $this->hasOne(Persona::class, 'persona_id');
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

        $array_personas = fn ($record) => PersonaHelper::getPersonasUsuario($record?->persona_id);

        $resultado = [
            TextInput::make('name')
            ->label('Nombre de Usuario')
            ->required(),
                
            TextInput::make('email')
            ->label('Correo Electrónico')
            ->required()
            ->email()
            ->default(function () {
                // Captura el 'persona_id' enviado desde la URL
                $personaId = request()->query('persona_id');
                
                if ($personaId) {
                    // Busca la persona y retorna su correo
                    return Persona::find($personaId)?->email;
                }
                
                return null;
            }),

            TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->required(function (string $context, $record): bool {
                // 1. En contexto de creación general, siempre es obligatoria
                if ($context === 'create') {
                    return true;
                }

                // 2. En contexto de edición, evaluamos según el modelo que disparó el formulario
                if ($context === 'edit' && $record) {
                    
                    // Si viene desde PersonaResource (se está editando una Persona para adjuntarle un Usuario)
                    if ($record instanceof Persona) {
                        return !$record->Usuario()->exists(); // Obligatoria si la persona no tiene usuario aún
                    }
                    
                    // Si viene desde UserResource (se está editando el Usuario directamente)
                    if ($record instanceof self) { // 'self' hace referencia al modelo User actual
                        return false; // Al editar un usuario directo, ya tiene contraseña (es opcional)
                    }
                }

                return false;
            })
            // Si el campo está vacío al guardar, no lo incluye en el Query de actualización
            ->dehydrated(fn (?string $state) => filled($state))
            // Encripta la contraseña automáticamente antes de guardarla (si se modificó)
            ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
            ->helperText(function (string $context, $record) {
                // Ajustamos el texto de ayuda según el modelo actual
                if ($context === 'edit' && $record) {
                    if ($record instanceof Persona && !$record->Usuario()->exists()) {
                        return 'Defina una contraseña para el nuevo usuario.';
                    }
                    return 'Deje este campo en blanco si no desea cambiar la contraseña.';
                }
                return null;
            }),

            Select::make('rol')
            ->label('Rol del Usuario')
            ->required()
            ->options(UserHelper::getOpcionesRoles())
            ->visible(fn ($record): bool => UserHelper::getShouldVisibleField($record))
            ->disabled(fn ($record): bool => UserHelper::getShouldDisableField($record)),

            Select::make("persona_id")
            ->label('Persona')
            ->placeholder("Sin adjuntar una Persona")
            ->searchable()
            ->nullable()
            ->visible($visible_persona_id)
            ->disabled(!$visible_persona_id)
            ->default(request()->query('persona_id'))
            ->options($array_personas),

        ];
        return $resultado;
    }

    public static function getOutSchema(string $mode_out): array{
        $resultado = match($mode_out){
            "table" => [
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
                
                TextColumn::make('rol')
                ->label('Rol')
                ->formatStateUsing(fn (string $state): string => UserHelper::getFormatStateRoles($state))
                ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('email_verified_at')
                ->label('Correo Verificado')
                ->dateTime('d/m/Y H:i:s')
                ->searchable()
                ->sortable()
                ->visible(auth()->user()->isAdmin())
                ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                ->dateTime('d/m/Y H:i:s')
                ->label('Fecha de Creación')
                ->sortable()
                ->visible(auth()->user()->isAdmin())
                ->toggleable(isToggledHiddenByDefault: true),
            ],
            'folist' => [
                TextEntry::make('name')
                ->label('Nombre de Usuario')
                ->placeholder('-'),

                TextEntry::make('email')
                ->label('Correo Electrónico')
                ->placeholder('-'),

                TextEntry::make('rol')
                ->label('Rol del Usuario')
                ->placeholder('-')
                ->formatStateUsing(fn (string $state): string => UserHelper::getFormatStateRoles($state)),
            ],
            default => [],
        };
        
        return $resultado;
    }
}


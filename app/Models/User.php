<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
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
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use RegistraAuditoria;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

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
        'habilitado',
        'deshabilitado_motivo',
        'deshabilitado_en',
    ];
    public function persona(): BelongsTo
    {
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
            'habilitado' => 'boolean',
            'deshabilitado_en' => 'datetime',
            'sesion_activa_desde' => 'datetime',
            'ultima_actividad_en' => 'datetime',
        ];
    }
    
    // al deshabilitar se anota el motivo y la fecha; al habilitar se limpian
    protected static function booted(): void
    {
        static::saving(function (User $usuario) {
            if (! $usuario->isDirty('habilitado')) {
                return;
            }

            if ($usuario->isHabilitado()) {
                $usuario->deshabilitado_motivo = null;
                $usuario->deshabilitado_en = null;
            } else {
                $usuario->deshabilitado_motivo ??= 'manual';
                $usuario->deshabilitado_en = now();
            }
        });
    }

    // un usuario recién creado todavía no trae la columna: cuenta como habilitado
    public function isHabilitado(): bool
    {
        return (bool) ($this->habilitado ?? true);
    }

    public function loginIntento(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LoginIntento::class);
    }

    // cierra todo lo que tenga abierto el usuario (sesiones del panel y tokens)
    public function cerrarSesiones(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $this->id)->delete();
        }

        $this->tokens()?->delete();
        \App\Services\SesionUnica::liberar($this);
    }

    // lo llama el trait RegistraAuditoria cuando cambia algo sensible del usuario
    public function auditoriaEspecial(array $cambios, array $antes): void
    {
        if (! array_key_exists('habilitado', $cambios)) {
            return;
        }

        $habilitado = (bool) $cambios['habilitado'];

        if (! $habilitado) {
            $this->cerrarSesiones();
        } else {
            // si lo habilita el administrador, empieza de cero con los intentos de clave
            \App\Services\ControlIntentosLogin::reiniciar($this);
        }

        Auditoria::registrar(
            evento: $habilitado ? 'usuario_habilitado' : 'usuario_deshabilitado',
            descripcion: ($habilitado ? 'Se habilitó' : 'Se deshabilitó') . " al usuario {$this->name}"
                . (! $habilitado ? " (motivo: {$this->deshabilitado_motivo})" : ''),
            modelo: $this,
            extra: ['motivo' => $this->deshabilitado_motivo],
        );
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
    public function canAccessPanel(Panel $panel): bool
    {
        // un usuario deshabilitado no entra aunque tenga la sesión abierta
        return $this->isHabilitado() && ($this->isAdmin() || $this->isRRHH() || $this->isFuncionario() || $this->isEmpleado());
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
            ->unique(ignoreRecord: true),

            TextInput::make('password')
            ->label('Contraseña')
            ->password()
            ->required(fn ($record): bool => ! $record?->exists)
            ->dehydrated(fn (?string $state) => filled($state))
            // quitá mutateDehydratedStateUsing: el cast 'hashed' del modelo ya hashea
            ->helperText(fn ($record) => $record?->exists
                ? 'Deje este campo en blanco si no desea cambiar la contraseña.'
            : null),

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

            \Filament\Forms\Components\Toggle::make('habilitado')
            ->label('Usuario habilitado')
            ->helperText('Un usuario deshabilitado no puede ingresar al sistema.')
            ->default(true)
            ->visible(fn (): bool => (bool) auth()->user()?->isAdmin())
            ->disabled(fn ($record): bool => $record?->id === auth()->id()),
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


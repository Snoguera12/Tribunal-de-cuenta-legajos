<?php

namespace App\Models;

use App\Models\Concerns\RegistraActividadReciente;
use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\SoftDeletes;
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
    use RegistraActividadReciente;
    use SoftDeletes;

    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $fillable = [
        'name',
        'email',
        'password',
        'rol',
        'habilitado',
        'deshabilitado_motivo',
        'deshabilitado_en',
        'debe_cambiar_clave',
        'clave_cambiada_en',
        'primer_ingreso_en',
        'persona_id',
        'sector',
    ];

    public const SECTORES = [
        'tic' => 'TIC (Tecnología de la Información y Comunicación)',
        'rrhh' => 'RRHH (Recursos Humanos)',
    ];
    // al deshabilitar se anota el motivo y la fecha; al habilitar se limpian
    protected static function booted(): void
    {
        // usuario nuevo: la clave que le pone el administrador es provisoria
        static::creating(function (User $usuario) {
            if (! array_key_exists('debe_cambiar_clave', $usuario->getAttributes())) {
                $usuario->debe_cambiar_clave = true;
            }
        });

        // aviso por mail (sin la clave). Si el correo falla no se corta la creacion
        static::created(function (User $usuario) {
            \Illuminate\Support\Facades\DB::afterCommit(function () use ($usuario) {
                try {
                    $usuario->notify(new \App\Notifications\UsuarioCreado());
                } catch (\Throwable $e) {
                    logger()->warning('No se pudo mandar el mail al usuario nuevo: ' . $e->getMessage());
                }
            });
        });

        // si el mismo usuario cambia su clave ya es propia; si se la cambia otro, vuelve a ser provisoria
        static::saving(function (User $usuario) {

            if ($usuario->exists && $usuario->isDirty('password')) {

                $propia = auth()->id() === $usuario->id;

                $usuario->debe_cambiar_clave = ! $propia;
                $usuario->clave_cambiada_en = $propia ? now() : null;
            }

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


    // un usuario recien creado todavia no trae la columna: cuenta como habilitado
    public function isHabilitado(): bool
    {
        return (bool) ($this->habilitado ?? true);
    }


    public function loginIntento(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(LoginIntento::class);
    }


    // cierra todo lo que tenga abierto el usuario
    public function cerrarSesiones(): void
    {
        if (\Illuminate\Support\Facades\Schema::hasTable('sessions')) {
            \Illuminate\Support\Facades\DB::table('sessions')->where('user_id', $this->id)->delete();
        }

        $this->tokens()?->delete();
        \App\Services\SesionUnica::liberar($this);
    }


    public function persona() : BelongsTo{
        return $this->belongsTo(Persona::class, 'persona_id');
    }


    // se llama desde el trait cuando cambia algo sensible del usuario
    public function auditoriaEspecial(array $cambios, array $antes): void
    {
        if (array_key_exists('habilitado', $cambios)) {

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

        if (array_key_exists('rol', $cambios)) {
            Auditoria::registrar(
                evento: 'cambio_rol',
                descripcion: "Cambió el rol de {$this->name}: {$antes['rol']} -> {$cambios['rol']}",
                modelo: $this,
                antes: ['rol' => $antes['rol']],
                despues: ['rol' => $cambios['rol']],
            );
        }

        if (array_key_exists('password', $cambios)) {
            Auditoria::registrar(
                evento: 'cambio_password',
                descripcion: "Cambió la contraseña de {$this->name}",
                modelo: $this,
            );
        }

        if (array_key_exists('email', $cambios)) {
            Auditoria::registrar(
                evento: 'cambio_email',
                descripcion: "Cambió el correo de {$this->name}",
                modelo: $this,
                antes: ['email' => $antes['email']],
                despues: ['email' => $cambios['email']],
            );
        }
    }
    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'habilitado' => 'boolean',
            'deshabilitado_en' => 'datetime',
            'debe_cambiar_clave' => 'boolean',
            'clave_cambiada_en' => 'datetime',
            'primer_ingreso_en' => 'datetime',
            'ultima_actividad_en' => 'datetime',
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
    // los dos administradores: uno del sector TIC y otro del sector RRHH
    public function isAdminTic(): bool
    {
        return $this->isAdmin() && $this->sector === 'tic';
    }
    public function isAdminRrhh(): bool
    {
        return $this->isAdmin() && $this->sector === 'rrhh';
    }
    public function getSectorNombreAttribute(): ?string
    {
        return $this->sector ? (self::SECTORES[$this->sector] ?? $this->sector) : null;
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
        // un usuario deshabilitado no entra aunque tenga la sesion abierta
        return $this->isHabilitado() && ($this->isAdmin() || $this->isRRHH() || $this->isFuncionario() || $this->isEmpleado());
    }
    // para "ultimos agentes y usuarios": un usuario modificado cuenta si cambio algo que se edita a mano
    public function actividadReciente(bool $creado = false): ?array
    {
        if (! $creado && ! $this->wasChanged(['name', 'email', 'rol', 'sector', 'persona_id', 'password'])) {
            return null;
        }

        return ['usuario', $this->id];
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
            ->unique(ignoreRecord: true)
            ->default(function () {
                // persona_id que viene en la url
                $personaId = request()->query('persona_id');

                if ($personaId) {
                    // su correo
                    return Persona::find($personaId)?->email;
                }

                return null;
            }),

            TextInput::make('password')
            ->label('Contraseña')
            ->password()
            // minimo 10, mayusculas, minusculas y numeros (solo si se escribe una)
            ->rule(\Illuminate\Validation\Rules\Password::min(10)->letters()->mixedCase()->numbers())
            ->required(fn ($record): bool => ! $record?->exists)
            // vacio = no se cambia la clave
            ->dehydrated(fn (?string $state) => filled($state))
            // el cast 'hashed' del modelo ya la guarda hasheada
            ->helperText(fn ($record) => $record?->exists
                ? 'Deje este campo en blanco si no desea cambiar la contraseña.'
                : null),

            \Filament\Forms\Components\Toggle::make('habilitado')
                ->label('Usuario habilitado')
                ->default(true)
                ->helperText('Se deshabilita solo si se da de baja el legajo de la persona.'),

            Select::make('rol')
            ->label('Rol del Usuario')
            ->required()
            ->live()
            ->options(UserHelper::getOpcionesRoles())
            ->visible(fn ($record): bool => UserHelper::getShouldVisibleField($record))
            ->disabled(fn ($record): bool => UserHelper::getShouldDisableField($record)),

            Select::make('sector')
            ->label('Sector del administrador')
            ->placeholder('Sin sector')
            ->options(self::SECTORES)
            ->native(false)
            ->nullable()
            ->visible(fn (Get $get, $record): bool => ($get('rol') ?? $record?->rol) === 'administrador' && auth()->user()?->isAdmin())
            ->rules([
                fn ($record) => function (string $attribute, $value, \Closure $fail) use ($record) {
                    if (! $value) {
                        return;
                    }

                    $yaHay = self::query()
                        ->where('rol', 'administrador')
                        ->where('sector', $value)
                        ->when($record, fn ($q) => $q->whereKeyNot($record->id))
                        ->exists();

                    if ($yaHay) {
                        $fail('Ya hay un administrador del sector ' . strtoupper($value) . '. Cambiale el sector al otro primero, o dejá este usuario sin sector.');
                    }
                },
            ])
            ->helperText('Hay un administrador del sector TIC y otro del sector RRHH (uno por sector).'),

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
                
                \Filament\Tables\Columns\IconColumn::make('habilitado')
                    ->label('Habilitado')
                    ->boolean(),

                // se puede buscar escribiendo el rol: "rrhh", "empleado", "administrador"...
                TextColumn::make('rol')
                ->label('Rol')
                ->formatStateUsing(fn (string $state): string => UserHelper::getFormatStateRoles($state))
                ->searchable(query: function ($query, string $search) {
                    $texto = mb_strtolower(trim($search));
                    $roles = collect(['empleado' => 'Empleado', 'funcionario' => 'Funcionario', 'rrhh' => 'RRHH', 'administrador' => 'Administrador'])
                        ->filter(fn ($etiqueta, $valor) => str_contains($valor, $texto) || str_contains(mb_strtolower($etiqueta), $texto))
                        ->keys()
                        ->all();

                    return $query->whereIn('users.rol', $roles);
                })
                ->sortable()
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


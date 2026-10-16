<?php

namespace App\Models;

use App\Models\Concerns\RegistraAuditoria;
use Illuminate\Database\Eloquent\SoftDeletes;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Hash;
use Database\Factories\UserFactory;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    use RegistraAuditoria;
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
    public static function getFormSchema(bool $visible_persona_id): array
    {
        return [
            TextInput::make('name')->label('Nombre de Usuario')
                ->required(),
                
            TextInput::make('email')->label('Correo Electrónico')
            ->email()
            ->default(function () {
                // persona_id que viene en la url
                $personaId = request()->query('persona_id');
                
                if ($personaId) {
                    // su correo
                    return Persona::find($personaId)?->email;
                }
                
                return null;
            })
            ->required(),

            TextInput::make('password')
            ->label('Contraseña')
            ->password()
            // minimo 10, mayusculas, minusculas y numeros (solo si se escribe una)
            ->rule(\Illuminate\Validation\Rules\Password::min(10)->letters()->mixedCase()->numbers())
            ->required(function (string $context, $record): bool {
                // al crear es obligatoria
                if ($context === 'create') {
                    return true;
                }

                // al editar depende de desde donde se abre el formulario
                if ($context === 'edit' && $record) {
                    
                    // desde Persona: obligatoria si todavia no tiene usuario
                    if ($record instanceof Persona) {
                        return !$record->Usuario()->exists();
                    }
                    
                    // desde Usuario: ya tiene clave, es opcional
                    if ($record instanceof self) {
                        return false;
                    }
                }

                return false;
            })
            // vacio = no se cambia la clave
            ->dehydrated(fn (?string $state) => filled($state))
            // se guarda hasheada
            ->mutateDehydratedStateUsing(fn (string $state) => Hash::make($state))
            ->helperText(function (string $context, $record) {
                // el texto cambia segun desde donde se edita
                if ($context === 'edit' && $record) {
                    if ($record instanceof Persona && !$record->Usuario()->exists()) {
                        return 'Defina una contraseña para el nuevo usuario.';
                    }
                    return 'Deje este campo en blanco si no desea cambiar la contraseña.';
                }
                return null;
            }),

            \Filament\Forms\Components\Toggle::make('habilitado')
                ->label('Usuario habilitado')
                ->default(true)
                ->helperText('Se deshabilita solo si se da de baja el legajo de la persona.'),

            Select::make('rol')->label('Rol del Usuario')
            ->options([
                'empleado' => 'Empleado',
                'funcionario' => 'Funcionario',
                'rrhh' => 'RRHH (Recursos Humanos)',
            ])
            ->rule('in:empleado,funcionario,rrhh')
            ->required(),

            Select::make("persona_id")->label("Persona")
            ->visible($visible_persona_id)
            ->searchable()
            ->nullable()
            ->default(fn () => request()->query('persona_id'))
            ->placeholder("Ninguna persona")
            ->options(Persona::getPersonas()),
        ];
    }

    public static function getOutSchema(string $mode_out): array{
        $resultado = match($mode_out){
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
                
                \Filament\Tables\Columns\IconColumn::make('habilitado')
                    ->label('Habilitado')
                    ->boolean(),

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


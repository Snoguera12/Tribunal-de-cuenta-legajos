<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\User;
use App\Notifications\IncidenteAbierto;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Notification as Aviso;
use Illuminate\Support\Facades\Log;


class DetectorIncidentes
{

    // para que abrir un incidente no dispare otra evaluacion
    private static bool $ocupado = false;


    // se llama despues de guardar cada registro de auditoria
    public static function evaluar(Auditoria $registro): void
    {
        if (self::$ocupado) {
            return;
        }

        self::$ocupado = true;

        try {

            $limite = config('sistema.incidentes');
            $desde = now()->subMinutes($limite['minutos']);

            switch ($registro->evento) {

                case 'login_fallido':
                    self::loginFallido($registro, $desde, $limite);
                    break;

                case 'login':
                    self::loginExitoso($registro, $desde);
                    break;

                case 'acceso_denegado':
                    $cuantos = Auditoria::where('evento', 'acceso_denegado')
                        ->where('created_at', '>=', $desde)
                        ->where(fn ($q) => $registro->actor_id
                            ? $q->where('actor_id', $registro->actor_id)
                            : $q->where('ip', $registro->ip))
                        ->count();

                    if ($cuantos >= $limite['accesos_denegados']) {
                        self::abrir('acceso_no_autorizado', 'alta',
                            "{$cuantos} accesos denegados en {$limite['minutos']} minutos", $registro);
                    }
                    break;

                case 'eliminado':
                case 'eliminado_definitivo':
                    $cuantos = Auditoria::whereIn('evento', ['eliminado', 'eliminado_definitivo'])
                        ->where('created_at', '>=', $desde)
                        ->where('actor_id', $registro->actor_id)
                        ->where('actor_tipo', $registro->actor_tipo)
                        ->count();

                    if ($registro->actor_id && $cuantos >= $limite['borrados']) {
                        self::abrir('borrado_masivo', 'critica',
                            "{$registro->actor_nombre} borró {$cuantos} registros en {$limite['minutos']} minutos", $registro);
                    }
                    break;

                case 'archivo_rechazado':
                    self::abrir('archivo_malicioso', 'alta', $registro->descripcion, $registro, 0);
                    break;

                case 'cambio_rol':
                    self::abrir('cambio_privilegios', 'alta', $registro->descripcion, $registro, 0);
                    break;

                case 'error_sistema':
                    $cuantos = Auditoria::where('evento', 'error_sistema')
                        ->where('created_at', '>=', $desde)->count();

                    if ($cuantos >= $limite['errores']) {
                        self::abrir('fallas_repetidas', 'alta',
                            "{$cuantos} errores del sistema en {$limite['minutos']} minutos", $registro);
                    }
                    break;

                case 'backup_fallo':
                    self::abrir('backup_fallido', 'critica', $registro->descripcion, $registro, 0);
                    break;

                case 'backup_viejo':
                    self::abrir('backup_desactualizado', 'alta', $registro->descripcion, $registro, 60 * 12);
                    break;

                case 'cuenta_bloqueada_temporal':
                    self::abrir('cuenta_bloqueada', 'alta', $registro->descripcion, $registro, 60);
                    break;

                case 'cuenta_bloqueada_permanente':
                    self::abrir('cuenta_bloqueada_permanente', 'critica', $registro->descripcion, $registro, 0);
                    break;

                case 'sesion_duplicada_bloqueada':
                    $cuantos = Auditoria::where('evento', 'sesion_duplicada_bloqueada')
                        ->where('actor_id', $registro->actor_id)
                        ->where('created_at', '>=', now()->subMinutes(30))->count();

                    if ($cuantos >= 3) {
                        self::abrir('sesion_compartida', 'alta', "{$registro->actor_nombre}: {$cuantos} ingresos duplicados en 30 minutos", $registro, 120);
                    }
                    break;

                case 'auditoria_alterada':
                    self::abrir('auditoria_alterada', 'critica', $registro->descripcion, $registro, 60 * 6);
                    break;

                case 'historico_error':
                    self::abrir('historico_fallido', 'critica', $registro->descripcion, $registro, 30);
                    break;
            }

        } catch (\Throwable $e) {

            Log::error('Falló el detector de incidentes: ' . $e->getMessage());

        } finally {

            self::$ocupado = false;
        }
    }


    private static function loginFallido(Auditoria $registro, $desde, array $limite): void
    {
        $fallos = Auditoria::where('evento', 'login_fallido')->where('created_at', '>=', $desde);

        $porIp = (clone $fallos)->where('ip', $registro->ip)->count();

        if ($registro->ip && $porIp >= $limite['intentos_login']) {
            self::abrir('fuerza_bruta', 'alta',
                "{$porIp} intentos fallidos desde la IP {$registro->ip} en {$limite['minutos']} minutos", $registro);
        }

        $correo = $registro->extra['email'] ?? null;

        if ($correo) {
            $porCuenta = (clone $fallos)->where('extra', 'like', '%' . $correo . '%')->count();

            if ($porCuenta >= $limite['intentos_login']) {
                self::abrir('ataque_cuenta', 'alta',
                    "{$porCuenta} intentos fallidos contra la cuenta {$correo}", $registro);
            }
        }
    }


    private static function loginExitoso(Auditoria $registro, $desde): void
    {
        // entro bien despues de varios fallos desde la misma ip
        $fallos = Auditoria::where('evento', 'login_fallido')
            ->where('created_at', '>=', $desde)
            ->where('ip', $registro->ip)
            ->count();

        if ($registro->ip && $fallos >= 3) {
            self::abrir('login_tras_fallos', 'alta',
                "{$registro->actor_nombre} entró después de {$fallos} intentos fallidos desde {$registro->ip}", $registro);
        }

        // ip que nunca uso antes
        if ($registro->actor_id && $registro->ip) {

            $anteriores = Auditoria::where('evento', 'login')
                ->where('actor_id', $registro->actor_id)
                ->where('id', '<', $registro->id);

            if ($anteriores->exists() && ! (clone $anteriores)->where('ip', $registro->ip)->exists()) {
                Auditoria::registrar(
                    evento: 'sesion_nueva_ip',
                    descripcion: "{$registro->actor_nombre} ingresó desde una IP que no usaba antes ({$registro->ip})",
                    actor: User::find($registro->actor_id),
                );
            }
        }
    }


    // abre un incidente. $repetirMinutos evita repetirlo (0 = siempre abre uno nuevo)
    public static function abrir(string $tipo, string $severidad, string $descripcion, ?Auditoria $origen = null, ?int $repetirMinutos = null): ?Incidente
    {
        $repetirMinutos ??= config('sistema.incidentes.minutos') * 3;

        if ($repetirMinutos > 0) {

            $repetido = Incidente::where('tipo', $tipo)
                ->where('estado', 'abierto')
                ->where('created_at', '>=', now()->subMinutes($repetirMinutos))
                ->when($origen?->ip, fn ($q, $ip) => $q->where('ip', $ip))
                ->when(! $origen?->ip && $origen?->actor_id, fn ($q) => $q->where('actor_id', $origen->actor_id))
                ->exists();

            if ($repetido) {
                return null;
            }
        }

        $incidente = Incidente::create([
            'tipo' => $tipo,
            'severidad' => $severidad,
            'descripcion' => $descripcion,
            'actor_id' => $origen?->actor_id,
            'actor_nombre' => $origen?->actor_nombre,
            'ip' => $origen?->ip,
            'mac' => $origen?->mac,
            'auditoria_id' => $origen?->id,
            'detalle' => [
                'evento_origen' => $origen?->evento,
                'navegador' => $origen?->sistema_operativo,
                'url' => $origen?->url,
            ],
        ]);

        Auditoria::registrar(
            evento: 'incidente_abierto',
            descripcion: 'Incidente #' . $incidente->id . ': ' . (Incidente::tipos()[$tipo] ?? $tipo),
            extra: ['incidente_id' => $incidente->id, 'tipo' => $tipo],
            severidad: $severidad,
        );

        self::avisarAdministradores($incidente);

        return $incidente;
    }


    // notificacion dentro del panel, solo le llega a los administradores
    private static function avisarAdministradores(Incidente $incidente): void
    {
        try {

            $admins = User::where('rol', 'administrador')->get();

            if ($admins->isEmpty()) {
                return;
            }

            Notification::make()
                ->title('Incidente de seguridad: ' . (Incidente::tipos()[$incidente->tipo] ?? $incidente->tipo))
                ->body($incidente->descripcion)
                ->danger()
                ->sendToDatabase($admins);

        } catch (\Throwable $e) {

            Log::warning('No se pudo notificar el incidente: ' . $e->getMessage());
        }

        self::avisarPorCorreo($incidente);
    }


    // correos de los administradores habilitados + los extra del .env
    public static function destinosCorreo(): array
    {
        $admins = User::where('rol', 'administrador')->where('habilitado', true)->pluck('email')->all();

        return array_values(array_unique(array_filter([...$admins, ...config('sistema.alertas.destinos')])));
    }


    // opcional (ALERTAS_CORREO=true). Si el correo falla no pasa nada
    private static function avisarPorCorreo(Incidente $incidente): void
    {
        if (! config('sistema.alertas.correo')) {
            return;
        }

        $orden = ['info' => 0, 'aviso' => 1, 'alta' => 2, 'critica' => 3];

        if (($orden[$incidente->severidad] ?? 0) < ($orden[config('sistema.alertas.desde')] ?? 2)) {
            return;
        }

        try {

            $destinos = self::destinosCorreo();

            if ($destinos) {
                Aviso::route('mail', $destinos)->notify(new IncidenteAbierto($incidente));
            }

        } catch (\Throwable $e) {

            Log::warning('No se pudo mandar el correo del incidente: ' . $e->getMessage());
        }
    }
}

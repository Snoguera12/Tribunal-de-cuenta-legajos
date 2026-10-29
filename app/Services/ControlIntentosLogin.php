<?php

namespace App\Services;

use App\Models\Auditoria;
use App\Models\LoginIntento;
use App\Models\User;
use Illuminate\Support\Facades\Cache;


/*
 * reglas de los intentos de clave (ver config/sistema.php, 'login').
 * Si el correo no existe se lleva la cuenta igual (en cache) para que los mensajes
 * sean los mismos y no se pueda saber si un correo esta registrado.
 */
class ControlIntentosLogin
{

    public static function normalizar(?string $email): string
    {
        return mb_strtolower(trim((string) $email));
    }


    private static function cfg(string $clave)
    {
        return config('sistema.login.' . $clave);
    }


    private static function usuario(string $email): ?User
    {
        return User::whereRaw('lower(email) = ?', [$email])->first();
    }


    private static function vacio(): array
    {
        return [
            'fallos_tanda' => 0, 'fallos_totales' => 0, 'tandas_agotadas' => 0, 'reinicios' => 0,
            'ultimo_fallo_en' => null, 'proximo_intento_en' => null, 'bloqueado_hasta' => null, 'permanente' => false,
        ];
    }


    private static function cargar(string $email, ?User $usuario): array
    {
        if (! $usuario) {
            return Cache::get('login_' . hash('sha256', $email), self::vacio());
        }

        $fila = LoginIntento::where('user_id', $usuario->id)->first();

        if (! $fila) {
            return self::vacio();
        }

        return [
            'fallos_tanda' => $fila->fallos_tanda, 'fallos_totales' => $fila->fallos_totales,
            'tandas_agotadas' => $fila->tandas_agotadas, 'reinicios' => $fila->reinicios,
            'ultimo_fallo_en' => $fila->ultimo_fallo_en?->timestamp, 'proximo_intento_en' => $fila->proximo_intento_en?->timestamp,
            'bloqueado_hasta' => $fila->bloqueado_hasta?->timestamp, 'permanente' => $fila->permanente,
        ];
    }


    private static function guardar(string $email, ?User $usuario, array $estado): void
    {
        if (! $usuario) {
            Cache::put('login_' . hash('sha256', $email), $estado, now()->addDays(2));

            return;
        }

        $fecha = fn ($segundos) => $segundos ? now()->setTimestamp($segundos) : null;

        LoginIntento::updateOrCreate(['user_id' => $usuario->id], [
            'fallos_tanda' => $estado['fallos_tanda'], 'fallos_totales' => $estado['fallos_totales'],
            'tandas_agotadas' => $estado['tandas_agotadas'], 'reinicios' => $estado['reinicios'],
            'ultimo_fallo_en' => $fecha($estado['ultimo_fallo_en']), 'proximo_intento_en' => $fecha($estado['proximo_intento_en']),
            'bloqueado_hasta' => $fecha($estado['bloqueado_hasta']), 'permanente' => $estado['permanente'],
        ]);
    }


    // aplica lo que paso con el tiempo: fin del bloqueo de 1 hora y devolucion de los 4 intentos
    private static function avanzar(string $email, ?User $usuario, array $estado): array
    {
        $ahora = now()->timestamp;
        $cambio = false;

        if ($estado['permanente']) {
            return $estado;
        }

        if ($estado['bloqueado_hasta'] && $estado['bloqueado_hasta'] <= $ahora) {
            // termino la hora: empieza una tanda nueva de 5 intentos
            $estado['bloqueado_hasta'] = null;
            $estado['proximo_intento_en'] = null;
            $estado['fallos_tanda'] = 0;
            $cambio = true;
        }

        $diezMinutos = $estado['ultimo_fallo_en'] && $estado['ultimo_fallo_en'] <= $ahora - self::cfg('reinicio_minutos') * 60;

        if (! $estado['bloqueado_hasta']
            && $estado['fallos_tanda'] === self::cfg('reinicio_intentos')
            && $estado['reinicios'] < self::cfg('reinicios_maximos')
            && $diezMinutos) {

            // se frenó en 4: pasados 10 minutos se le devuelven esos intentos
            $estado['fallos_tanda'] = 0;
            $estado['proximo_intento_en'] = null;
            $estado['reinicios']++;
            $cambio = true;

            if ($usuario) {
                Auditoria::registrar(
                    evento: 'intentos_restaurados',
                    descripcion: "Se devolvieron los intentos de clave a {$usuario->name} (reinicio {$estado['reinicios']} de " . self::cfg('reinicios_maximos') . ')',
                    actor: $usuario,
                );
            }
        }

        if ($cambio) {
            self::guardar($email, $usuario, $estado);
        }

        return $estado;
    }


    public static function restantes(array $estado): int
    {
        return max(0, min(
            self::cfg('intentos_por_tanda') - $estado['fallos_tanda'],
            self::cfg('tope_total') - $estado['fallos_totales'],
        ));
    }


    // antes de mirar la clave: devuelve el mensaje si hay bloqueo o hay que esperar, o null si puede intentar
    public static function bloqueoActual(string $email): ?string
    {
        $email = self::normalizar($email);
        $usuario = self::usuario($email);
        $estado = self::avanzar($email, $usuario, self::cargar($email, $usuario));
        $ahora = now()->timestamp;

        if ($estado['permanente']) {
            return self::mensajePermanente();
        }

        if ($estado['bloqueado_hasta'] && $estado['bloqueado_hasta'] > $ahora) {
            $minutos = (int) ceil(($estado['bloqueado_hasta'] - $ahora) / 60);

            return 'Tu usuario está bloqueado temporalmente. Podés volver a intentar en ' . $minutos . ' minuto(s), a las '
                . now()->setTimestamp($estado['bloqueado_hasta'])->format('H:i') . '.';
        }

        if ($estado['proximo_intento_en'] && $estado['proximo_intento_en'] > $ahora) {
            return 'Esperá ' . ($estado['proximo_intento_en'] - $ahora) . ' segundos para volver a intentar. Te quedan '
                . self::restantes($estado) . ' intento(s).';
        }

        return null;
    }


    // se llama cuando la clave fue incorrecta. Devuelve el mensaje para mostrar
    public static function registrarFallo(string $email): string
    {
        $email = self::normalizar($email);
        $usuario = self::usuario($email);
        $estado = self::avanzar($email, $usuario, self::cargar($email, $usuario));
        $ahora = now()->timestamp;

        $estado['fallos_tanda']++;
        $estado['fallos_totales']++;
        $estado['ultimo_fallo_en'] = $ahora;

        $resultado = 'normal';

        if ($estado['fallos_totales'] >= self::cfg('tope_total')) {

            $estado['permanente'] = true;
            $resultado = 'permanente';

        } elseif ($estado['fallos_tanda'] >= self::cfg('intentos_por_tanda')) {

            $estado['tandas_agotadas']++;
            $estado['proximo_intento_en'] = null;

            if ($estado['tandas_agotadas'] >= self::cfg('tandas_hasta_bloqueo_definitivo')) {
                $estado['permanente'] = true;
                $resultado = 'permanente';
            } else {
                $estado['bloqueado_hasta'] = $ahora + self::cfg('bloqueo_minutos') * 60;
                $resultado = 'temporal';
            }

        } else {

            $estado['proximo_intento_en'] = $ahora + self::cfg('espera_segundos');

            if (self::restantes($estado) === 1) {
                $resultado = 'ultimo_intento';
            }
        }

        self::guardar($email, $usuario, $estado);

        if ($usuario) {
            self::avisar($usuario, $resultado, $estado);
        }

        return self::mensajeTrasFallo($resultado, $estado);
    }


    private static function mensajePermanente(): string
    {
        return 'Superaste los intentos permitidos y tu usuario quedó bloqueado. Pedile al administrador que lo habilite.';
    }


    private static function mensajeTrasFallo(string $resultado, array $estado): string
    {
        if ($resultado === 'permanente') {
            return self::mensajePermanente();
        }

        if ($resultado === 'temporal') {
            $hasta = now()->setTimestamp($estado['bloqueado_hasta'])->format('H:i');
            $siguientes = self::restantes(['fallos_tanda' => 0] + $estado);

            return "Clave incorrecta. Tu usuario quedó bloqueado por " . self::cfg('bloqueo_minutos') . " minutos (hasta las {$hasta}). "
                . "Después vas a tener {$siguientes} intentos más; si también fallan, el bloqueo será definitivo.";
        }

        $texto = 'Clave incorrecta. Esperá ' . self::cfg('espera_segundos') . ' segundos para volver a intentar. Te quedan '
            . self::restantes($estado) . ' intento(s).';

        if ($resultado === 'ultimo_intento') {
            $texto .= $estado['tandas_agotadas'] + 1 >= self::cfg('tandas_hasta_bloqueo_definitivo')
                ? ' Si falla, el bloqueo será definitivo.'
                : ' Si falla, tu usuario se bloquea por ' . self::cfg('bloqueo_minutos') . ' minutos.';
        }

        return $texto;
    }


    // deja constancia y avisa a los administradores (el detector abre el incidente)
    private static function avisar(User $usuario, string $resultado, array $estado): void
    {
        $datos = ['fallos_tanda' => $estado['fallos_tanda'], 'fallos_totales' => $estado['fallos_totales']];

        if ($resultado === 'ultimo_intento') {
            Auditoria::registrar(
                evento: 'login_advertencia',
                descripcion: "A {$usuario->name} le queda un solo intento de clave antes del bloqueo",
                actor: $usuario,
                extra: $datos,
            );
        }

        if ($resultado === 'temporal') {
            Auditoria::registrar(
                evento: 'cuenta_bloqueada_temporal',
                descripcion: "El usuario {$usuario->name} quedó bloqueado " . self::cfg('bloqueo_minutos') . ' minutos por claves incorrectas',
                actor: $usuario,
                extra: $datos,
            );
        }

        if ($resultado === 'permanente') {

            // queda deshabilitado hasta que el administrador lo habilite
            if ($usuario->isHabilitado() && ! $usuario->isAdmin()) {
                $usuario->forceFill(['habilitado' => false, 'deshabilitado_motivo' => 'bloqueo_login'])->save();
            }

            Auditoria::registrar(
                evento: 'cuenta_bloqueada_permanente',
                descripcion: "El usuario {$usuario->name} quedó bloqueado hasta que lo habilite el administrador ({$estado['fallos_totales']} claves incorrectas)",
                actor: $usuario,
                extra: $datos,
            );
        }
    }


    // clave correcta: se borra todo el historial de fallos
    public static function reiniciar(User $usuario): void
    {
        LoginIntento::where('user_id', $usuario->id)->delete();
        Cache::forget('login_' . hash('sha256', self::normalizar($usuario->email)));
    }


    // lo usa el administrador para dar permiso otra vez
    public static function desbloquear(User $usuario, ?User $por = null): void
    {
        self::reiniciar($usuario);

        if (! $usuario->isHabilitado() && $usuario->deshabilitado_motivo === 'bloqueo_login') {
            $usuario->forceFill(['habilitado' => true])->save();
        }

        Auditoria::registrar(
            evento: 'cuenta_desbloqueada',
            descripcion: "Se desbloqueó al usuario {$usuario->name}" . ($por ? " (por {$por->name})" : ''),
            modelo: $usuario,
        );
    }


    // para mostrar en la lista de usuarios: null, 'temporal' o 'permanente'
    public static function estadoDe(User $usuario): ?string
    {
        $fila = $usuario->relationLoaded('loginIntento') ? $usuario->loginIntento : LoginIntento::where('user_id', $usuario->id)->first();

        if ($fila?->permanente || (! $usuario->isHabilitado() && $usuario->deshabilitado_motivo === 'bloqueo_login')) {
            return 'permanente';
        }

        return $fila?->bloqueado_hasta?->isFuture() ? 'temporal' : null;
    }
}

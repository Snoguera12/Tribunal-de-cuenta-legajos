<?php

namespace App\Console\Commands;

use App\Models\Auditoria;
use App\Models\Incidente;
use App\Models\User;
use App\Notifications\ResumenDiario;
use App\Services\DetectorIncidentes;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;


class SistemaResumenDiario extends Command
{
    protected $signature = 'sistema:resumen-diario';

    protected $description = 'Manda a los administradores un resumen de las ultimas 24 horas';


    public function handle(): int
    {
        $desde = now()->subDay();
        $cuantos = fn (string $evento) => Auditoria::where('evento', $evento)->where('created_at', '>=', $desde)->count();
        $ultimoBackup = Auditoria::where('evento', 'backup_ok')->latest('id')->first();

        $datos = [
            'Período' => $desde->format('d/m/Y H:i') . ' a ' . now()->format('d/m/Y H:i'),
            'Ingresos correctos' => $cuantos('login'),
            'Ingresos fallidos' => $cuantos('login_fallido'),
            'Accesos denegados' => $cuantos('acceso_denegado'),
            'Archivos rechazados' => $cuantos('archivo_rechazado'),
            'Errores del sistema' => $cuantos('error_sistema'),
            'Incidentes nuevos' => Incidente::where('created_at', '>=', $desde)->count(),
            'Incidentes abiertos en total' => Incidente::where('estado', 'abierto')->count(),
            'Usuarios deshabilitados' => User::where('habilitado', false)->count(),
            'Usuarios con clave provisoria hace más de 7 días' => User::where('debe_cambiar_clave', true)->where('created_at', '<', now()->subDays(7))->count(),
            'Cuentas bloqueadas por claves incorrectas' => \App\Models\LoginIntento::where('permanente', true)->orWhere('bloqueado_hasta', '>', now())->count(),
            'Último backup' => $ultimoBackup ? $ultimoBackup->created_at->format('d/m/Y H:i') : 'NO HAY',
        ];

        foreach ($datos as $titulo => $valor) {
            $this->line("{$titulo}: {$valor}");
        }

        $destinos = DetectorIncidentes::destinosCorreo();

        if (! config('sistema.alertas.correo') || empty($destinos)) {
            $this->comment('Las alertas por correo están apagadas (ALERTAS_CORREO) o no hay destinatarios.');

            return self::SUCCESS;
        }

        Notification::route('mail', $destinos)->notify(new ResumenDiario($datos));

        $this->info('Resumen enviado a ' . count($destinos) . ' destinatario(s).');

        return self::SUCCESS;
    }
}

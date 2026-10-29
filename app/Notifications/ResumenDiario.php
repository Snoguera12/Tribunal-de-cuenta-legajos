<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class ResumenDiario extends Notification
{

    public function __construct(public array $datos)
    {
    }


    public function via($notifiable): array
    {
        return ['mail'];
    }


    public function toMail($notifiable): MailMessage
    {
        $mensaje = (new MailMessage)->subject('Resumen diario del sistema de legajos');

        foreach ($this->datos as $titulo => $valor) {
            $mensaje->line("{$titulo}: {$valor}");
        }

        return $mensaje;
    }
}

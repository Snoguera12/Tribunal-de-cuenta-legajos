<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


// mail para el usuario nuevo. NUNCA lleva la clave
class UsuarioCreado extends Notification
{

    public function via($notifiable): array
    {
        return ['mail'];
    }


    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu usuario del sistema de legajos fue creado')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Se creó tu usuario en el sistema de legajos del Tribunal de Cuentas.')
            ->line('Ingresá con la clave que te dio el administrador. Esa clave es provisoria: en tu primer ingreso el sistema te va a pedir que la cambies por una propia.')
            ->action('Ir al sistema', url('/legajos'))
            ->line('No compartas tu clave con nadie. El administrador nunca te la va a pedir.');
    }
}

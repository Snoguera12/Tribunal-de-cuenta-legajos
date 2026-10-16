<?php

namespace App\Notifications;

use App\Filament\Resources\Incidentes\IncidenteResource;
use App\Models\Incidente;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class IncidenteAbierto extends Notification
{

    public function __construct(public Incidente $incidente)
    {
    }


    public function via($notifiable): array
    {
        return ['mail'];
    }


    public function toMail($notifiable): MailMessage
    {
        $i = $this->incidente;

        return (new MailMessage)
            ->subject('[' . strtoupper($i->severidad) . '] Incidente de seguridad: ' . (Incidente::tipos()[$i->tipo] ?? $i->tipo))
            ->line($i->descripcion)
            ->line('Usuario: ' . ($i->actor_nombre ?? '-'))
            ->line('IP: ' . ($i->ip ?? '-') . ' / MAC: ' . ($i->mac ?? 'no disponible'))
            ->line('Fecha: ' . $i->created_at->format('d/m/Y H:i:s'))
            ->action('Ver el incidente', IncidenteResource::getUrl('edit', ['record' => $i->id]));
    }
}

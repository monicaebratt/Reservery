<?php

namespace App\Notifications;

use App\Models\Organizacion;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InvitacionOrganizacion extends Notification
{
    public function __construct(public Organizacion $organizacion, public string $rol)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $rol = $this->rol === 'admin' ? 'administrador' : 'usuario';

        return (new MailMessage)
            ->subject("{$this->organizacion->nombre} te invitó a Reservery")
            ->greeting('¡Hola!')
            ->line("{$this->organizacion->nombre} te dio acceso a Reservery como {$rol}.")
            ->line('Crea tu cuenta con este mismo correo (o entra con Google) y quedarás unido automáticamente.')
            ->action('Crear mi cuenta', route('register'))
            ->line("Si te piden un código de invitación, es: {$this->organizacion->codigo_invitacion}")
            ->salutation('Equipo Reservery');
    }
}

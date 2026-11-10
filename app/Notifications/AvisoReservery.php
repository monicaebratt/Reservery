<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Correo que acompaña cada notificación de Reservery (RF11). */
class AvisoReservery extends Notification
{
    use Queueable;

    public function __construct(public string $titulo, public string $mensaje, public ?string $url = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $correo = (new MailMessage)
            ->subject($this->titulo.' · Reservery')
            ->greeting('Hola, '.($notifiable->name ?? '').'')
            ->line($this->mensaje);

        if ($this->url) {
            $correo->action('Ver en Reservery', $this->url);
        }

        return $correo->salutation('Equipo Reservery');
    }
}

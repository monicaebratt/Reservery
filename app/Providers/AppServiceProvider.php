<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Paginación con estilos de Bootstrap
        Paginator::useBootstrapFive();

        // Correo de "olvidé mi contraseña" en español
        ResetPassword::toMailUsing(function ($usuario, string $token) {
            $url = route('password.reset', ['token' => $token, 'email' => $usuario->email]);

            return (new MailMessage)
                ->subject('Restablece tu contraseña de Reservery')
                ->greeting('¡Hola, '.$usuario->name.'!')
                ->line('Recibimos una solicitud para restablecer la contraseña de tu cuenta.')
                ->action('Restablecer contraseña', $url)
                ->line('Este enlace vence en 60 minutos.')
                ->line('Si no fuiste tú, puedes ignorar este correo.')
                ->salutation('Equipo Reservery');
        });
    }
}

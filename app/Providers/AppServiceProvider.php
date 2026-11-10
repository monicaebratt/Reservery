<?php

namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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

        // Máximo 5 intentos por minuto en cada formulario de acceso (login, registro,
        // contraseña, código), por IP. Frena a quien prueba contraseñas una tras otra.
        RateLimiter::for('acceso', fn (Request $request) => Limit::perMinute(5)
            ->by($request->path().'|'.$request->ip())
            ->response(fn () => $request->expectsJson()
                ? response()->json(['mensaje' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'], 429)
                : back()->withErrors(['email' => 'Demasiados intentos. Espera un minuto e inténtalo de nuevo.'])->withInput($request->except('password'))));

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

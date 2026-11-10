<?php

namespace App\Listeners;

use App\Events\ReservaCambioDeEstado;
use App\Services\Notificaciones\NotificacionService;

/** RF11 / HU20: toda transición de estado notifica a los involucrados. */
class NotificarCambioDeEstado
{
    public function __construct(private NotificacionService $notificaciones)
    {
    }

    public function handle(ReservaCambioDeEstado $e): void
    {
        $reserva = $e->reserva->loadMissing(['usuario', 'objetos']);
        $objetos = $reserva->objetos->pluck('nombre')->join(', ');
        $quien = $e->responsable?->name ?? 'El sistema';
        $motivo = $e->motivo ? " Motivo: {$e->motivo}" : '';

        $titulos = [
            'aprobada' => 'Reserva aprobada',
            'rechazada' => 'Reserva rechazada',
            'cancelada' => 'Reserva cancelada',
            'completada' => 'Reserva completada',
        ];

        // Dueño de la reserva + administradores, sin avisarle a quien hizo el cambio
        $destinatarios = collect([$reserva->usuario])
            ->concat($this->notificaciones->administradoresDe($reserva->organizacion_id))
            ->reject(fn ($u) => $e->responsable && $u->id === $e->responsable->id);

        // Los administradores solo reciben las cancelaciones; aprobaciones y demás le importan al dueño
        if ($e->nuevo !== 'cancelada') {
            $destinatarios = $destinatarios->where('id', $reserva->user_id);
        }

        $this->notificaciones->enviar(
            $destinatarios,
            $e->nuevo,
            $titulos[$e->nuevo] ?? 'Tu reserva cambió de estado',
            "{$quien} cambió la reserva #{$reserva->id} ({$objetos}, {$reserva->fecha->format('d/m/Y')} {$reserva->horario}) de {$e->anterior} a {$e->nuevo}.{$motivo}",
            $reserva,
        );
    }
}

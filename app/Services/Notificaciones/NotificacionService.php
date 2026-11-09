<?php

namespace App\Services\Notificaciones;

use App\Models\Notificacion;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\AvisoReservery;
use Illuminate\Support\Collection;

/** RF11: notificaciones automáticas dentro de la app y por correo. */
class NotificacionService
{
    /**
     * @param  User|iterable<User>  $destinatarios
     */
    public function enviar($destinatarios, string $tipo, string $titulo, string $mensaje, ?Reserva $reserva = null, ?string $url = null): void
    {
        $usuarios = $destinatarios instanceof User ? collect([$destinatarios]) : collect($destinatarios);

        foreach ($usuarios->filter()->unique('id') as $usuario) {
            Notificacion::create([
                'user_id' => $usuario->id,
                'reserva_id' => $reserva?->id,
                'tipo' => $tipo,
                'titulo' => $titulo,
                'mensaje' => $mensaje,
                'url' => $url ?? ($reserva ? route('reservas.show', $reserva->id) : null),
            ]);

            if (config('reservery.notificar_por_correo')) {
                try {
                    $usuario->notify(new AvisoReservery($titulo, $mensaje, $url ?? ($reserva ? route('reservas.show', $reserva->id) : null)));
                } catch (\Throwable $e) {
                    report($e); // un correo que falla no debe tumbar la operación
                }
            }
        }
    }

    /** Administradores de la organización de la reserva (los "involucrados" del lado de la gestión). */
    public function administradoresDe(int $organizacionId, ?User $excepto = null): Collection
    {
        return User::where('organizacion_id', $organizacionId)
            ->whereIn('rol', ['organizacion', 'admin'])
            ->when($excepto, fn ($q) => $q->where('id', '!=', $excepto->id))
            ->get();
    }
}

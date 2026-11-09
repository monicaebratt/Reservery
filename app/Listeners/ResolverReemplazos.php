<?php

namespace App\Listeners;

use App\Events\ReservaCambioDeEstado;
use App\Services\Prioridad\PrioridadService;

/** RF18: aprobar la reserva prioritaria confirma el reemplazo; rechazarla lo descarta. */
class ResolverReemplazos
{
    public function __construct(private PrioridadService $prioridad)
    {
    }

    public function handle(ReservaCambioDeEstado $e): void
    {
        $reserva = $e->reserva;

        if (! $reserva->reemplazosPropuestos()->exists()) {
            return;
        }

        match ($e->nuevo) {
            'aprobada' => $this->prioridad->confirmar($reserva->loadMissing('proposito'), $e->responsable),
            'rechazada', 'cancelada' => $this->prioridad->descartar($reserva),
            default => null,
        };
    }
}

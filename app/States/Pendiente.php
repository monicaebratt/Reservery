<?php

namespace App\States;

use App\Models\Reserva;

class Pendiente extends EstadoBase
{
    public function nombre(): string
    {
        return 'pendiente';
    }

    public function aprobar(Reserva $reserva): void
    {
        $reserva->cambiarEstado(new Aprobada());
    }

    public function rechazar(Reserva $reserva): void
    {
        $reserva->cambiarEstado(new Rechazada());
    }

    public function cancelar(Reserva $reserva): void
    {
        $reserva->cambiarEstado(new Cancelada());
    }

    public function accionesPermitidas(): array
    {
        return ['aprobar', 'rechazar', 'cancelar'];
    }

    public function puedeEditarse(): bool
    {
        return true;
    }
}

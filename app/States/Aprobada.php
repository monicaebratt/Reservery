<?php

namespace App\States;

use App\Models\Reserva;

class Aprobada extends EstadoBase
{
    public function nombre(): string
    {
        return 'aprobada';
    }

    public function cancelar(Reserva $reserva): void
    {
        $reserva->cambiarEstado(new Cancelada());
    }

    public function completar(Reserva $reserva): void
    {
        $reserva->cambiarEstado(new Completada());
    }

    public function accionesPermitidas(): array
    {
        return ['cancelar', 'completar'];
    }
}

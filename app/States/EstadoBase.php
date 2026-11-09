<?php

namespace App\States;

use App\Exceptions\TransicionInvalidaException;
use App\Models\Reserva;

/**
 * Por defecto toda acción está prohibida. Cada estado concreto solo
 * sobrescribe las transiciones que sí le están permitidas.
 */
abstract class EstadoBase implements EstadoReserva
{
    public function aprobar(Reserva $reserva): void
    {
        $this->prohibir('aprobar');
    }

    public function rechazar(Reserva $reserva): void
    {
        $this->prohibir('rechazar');
    }

    public function cancelar(Reserva $reserva): void
    {
        $this->prohibir('cancelar');
    }

    public function completar(Reserva $reserva): void
    {
        $this->prohibir('completar');
    }

    public function accionesPermitidas(): array
    {
        return [];
    }

    public function puedeEditarse(): bool
    {
        return false;
    }

    protected function prohibir(string $accion): never
    {
        throw new TransicionInvalidaException(
            "No se puede {$accion} una reserva en estado {$this->nombre()}."
        );
    }
}

<?php

namespace App\States;

use App\Models\Reserva;

/**
 * Patrón State: interfaz que define las acciones que todo estado de una reserva debe manejar.
 */
interface EstadoReserva
{
    public function nombre(): string;

    public function aprobar(Reserva $reserva): void;

    public function rechazar(Reserva $reserva): void;

    public function cancelar(Reserva $reserva): void;

    public function completar(Reserva $reserva): void;

    /** Acciones válidas en este estado (sirve para mostrar solo los botones permitidos). */
    public function accionesPermitidas(): array;

    /** Indica si la reserva todavía se puede editar en este estado. */
    public function puedeEditarse(): bool;
}

<?php

namespace App\Events;

use App\Models\Reserva;
use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;

class ReservaCambioDeEstado
{
    use Dispatchable;

    public function __construct(
        public Reserva $reserva,
        public string $anterior,
        public string $nuevo,
        public ?User $responsable = null,
        public ?string $motivo = null,
    ) {
    }
}

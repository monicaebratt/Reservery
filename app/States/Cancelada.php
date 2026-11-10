<?php

namespace App\States;

/** Estado final: no permite ninguna transición. */
class Cancelada extends EstadoBase
{
    public function nombre(): string
    {
        return 'cancelada';
    }
}

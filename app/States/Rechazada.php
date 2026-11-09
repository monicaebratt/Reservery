<?php

namespace App\States;

/** Estado final: no permite ninguna transición. */
class Rechazada extends EstadoBase
{
    public function nombre(): string
    {
        return 'rechazada';
    }
}
